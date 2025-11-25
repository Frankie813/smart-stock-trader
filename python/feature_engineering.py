"""
Feature Engineering for Day Trading
Calculates technical indicators optimized for intraday trading
"""

import pandas as pd
import numpy as np
from typing import Dict, List
import warnings
warnings.filterwarnings('ignore')


def calculate_rsi(series: pd.Series, period: int = 14) -> pd.Series:
    """
    Calculate Relative Strength Index (RSI)

    Args:
        series: Price series (typically close prices)
        period: Lookback period (default 14)

    Returns:
        RSI values (0-100)
    """
    delta = series.diff()
    gain = (delta.where(delta > 0, 0)).rolling(window=period).mean()
    loss = (-delta.where(delta < 0, 0)).rolling(window=period).mean()

    rs = gain / loss
    rsi = 100 - (100 / (1 + rs))

    return rsi


def calculate_macd(series: pd.Series, fast: int = 12, slow: int = 26, signal: int = 9) -> tuple:
    """
    Calculate MACD (Moving Average Convergence Divergence)

    Args:
        series: Price series (typically close prices)
        fast: Fast EMA period (default 12)
        slow: Slow EMA period (default 26)
        signal: Signal line period (default 9)

    Returns:
        Tuple of (macd_line, signal_line, histogram)
    """
    ema_fast = series.ewm(span=fast, adjust=False).mean()
    ema_slow = series.ewm(span=slow, adjust=False).mean()

    macd_line = ema_fast - ema_slow
    signal_line = macd_line.ewm(span=signal, adjust=False).mean()
    histogram = macd_line - signal_line

    return macd_line, signal_line, histogram


def calculate_bollinger_bands(series: pd.Series, period: int = 20, std_dev: int = 2) -> tuple:
    """
    Calculate Bollinger Bands

    Args:
        series: Price series
        period: Moving average period (default 20)
        std_dev: Number of standard deviations (default 2)

    Returns:
        Tuple of (upper_band, middle_band, lower_band)
    """
    middle_band = series.rolling(window=period).mean()
    std = series.rolling(window=period).std()

    upper_band = middle_band + (std * std_dev)
    lower_band = middle_band - (std * std_dev)

    return upper_band, middle_band, lower_band


def calculate_atr(df: pd.DataFrame, period: int = 14) -> pd.Series:
    """
    Calculate Average True Range (ATR)

    Args:
        df: DataFrame with high, low, close columns
        period: Lookback period (default 14)

    Returns:
        ATR values
    """
    high_low = df['high'] - df['low']
    high_close = np.abs(df['high'] - df['close'].shift())
    low_close = np.abs(df['low'] - df['close'].shift())

    ranges = pd.concat([high_low, high_close, low_close], axis=1)
    true_range = np.max(ranges, axis=1)

    atr = true_range.rolling(period).mean()

    return atr


def add_intraday_features(df: pd.DataFrame) -> pd.DataFrame:
    """
    Add intraday price action features

    These features capture what happens DURING the trading day,
    which is critical for day trading.

    Args:
        df: DataFrame with OHLCV data

    Returns:
        DataFrame with added intraday features
    """
    # Opening gap from previous close
    df['gap_pct'] = (df['open'] - df['close'].shift(1)) / df['close'].shift(1) * 100
    df['gap_direction'] = (df['gap_pct'] > 0).astype(int)  # 1 = gap up, 0 = gap down
    df['gap_size'] = df['gap_pct'].abs()

    # Intraday range and volatility
    df['intraday_range_pct'] = (df['high'] - df['low']) / df['open'] * 100
    df['intraday_high_pct'] = (df['high'] - df['open']) / df['open'] * 100
    df['intraday_low_pct'] = (df['open'] - df['low']) / df['open'] * 100

    # Close position within the day's range
    # 0 = closed at low, 1 = closed at high
    df['close_position'] = (df['close'] - df['low']) / (df['high'] - df['low'] + 0.0001)

    # Candle body and shadow analysis
    df['body_size_pct'] = np.abs(df['close'] - df['open']) / df['open'] * 100
    df['upper_shadow_pct'] = (df['high'] - df[['close', 'open']].max(axis=1)) / df['open'] * 100
    df['lower_shadow_pct'] = (df[['close', 'open']].min(axis=1) - df['low']) / df['open'] * 100

    # Actual day trading returns (open to close)
    df['open_to_close_pct'] = (df['close'] - df['open']) / df['open'] * 100
    df['open_to_high_pct'] = (df['high'] - df['open']) / df['open'] * 100
    df['open_to_low_pct'] = (df['open'] - df['low']) / df['open'] * 100

    # Candle pattern indicators
    df['is_green_candle'] = (df['close'] > df['open']).astype(int)
    df['is_red_candle'] = (df['close'] < df['open']).astype(int)
    df['is_doji'] = (df['body_size_pct'] < 0.1).astype(int)  # Small body

    # Ratio of body to total range
    df['body_to_range_ratio'] = df['body_size_pct'] / (df['intraday_range_pct'] + 0.0001)

    return df


def add_technical_indicators(df: pd.DataFrame, config: Dict) -> pd.DataFrame:
    """
    Add technical indicators based on configuration

    Args:
        df: DataFrame with OHLCV data
        config: Configuration dict with features_enabled

    Returns:
        DataFrame with added technical indicators
    """
    features = config.get('features_enabled', {})

    # Moving Averages
    if features.get('sma_10'):
        df['sma_10'] = df['close'].rolling(window=10).mean()
        df['price_vs_sma10'] = (df['close'] - df['sma_10']) / df['sma_10'] * 100

    if features.get('sma_50'):
        df['sma_50'] = df['close'].rolling(window=50).mean()
        df['price_vs_sma50'] = (df['close'] - df['sma_50']) / df['sma_50'] * 100
        df['above_sma50'] = (df['close'] > df['sma_50']).astype(int)

    if features.get('sma_200'):
        df['sma_200'] = df['close'].rolling(window=200).mean()
        df['price_vs_sma200'] = (df['close'] - df['sma_200']) / df['sma_200'] * 100
        df['above_sma200'] = (df['close'] > df['sma_200']).astype(int)

    # Exponential Moving Averages
    if features.get('ema_12'):
        df['ema_12'] = df['close'].ewm(span=12, adjust=False).mean()
        df['price_vs_ema12'] = (df['close'] - df['ema_12']) / df['ema_12'] * 100

    if features.get('ema_26'):
        df['ema_26'] = df['close'].ewm(span=26, adjust=False).mean()
        df['price_vs_ema26'] = (df['close'] - df['ema_26']) / df['ema_26'] * 100

    # RSI (Relative Strength Index)
    if features.get('rsi_7'):
        df['rsi_7'] = calculate_rsi(df['close'], 7)

    if features.get('rsi_14'):
        df['rsi_14'] = calculate_rsi(df['close'], 14)
        df['rsi_oversold'] = (df['rsi_14'] < 30).astype(int)
        df['rsi_overbought'] = (df['rsi_14'] > 70).astype(int)
        df['rsi_neutral'] = ((df['rsi_14'] >= 40) & (df['rsi_14'] <= 60)).astype(int)

    if features.get('rsi_21'):
        df['rsi_21'] = calculate_rsi(df['close'], 21)

    # MACD
    if features.get('macd') or features.get('macd_signal') or features.get('macd_histogram'):
        macd, signal, histogram = calculate_macd(df['close'])

        if features.get('macd'):
            df['macd'] = macd
            df['macd_positive'] = (macd > 0).astype(int)

        if features.get('macd_signal'):
            df['macd_signal'] = signal

        if features.get('macd_histogram'):
            df['macd_histogram'] = histogram
            df['macd_histogram_positive'] = (histogram > 0).astype(int)
            df['macd_histogram_increasing'] = (histogram > histogram.shift(1)).astype(int)

    # Bollinger Bands
    if any([features.get('bb_upper'), features.get('bb_middle'), features.get('bb_lower')]):
        upper, middle, lower = calculate_bollinger_bands(df['close'])

        if features.get('bb_upper'):
            df['bb_upper'] = upper

        if features.get('bb_middle'):
            df['bb_middle'] = middle

        if features.get('bb_lower'):
            df['bb_lower'] = lower

        if features.get('bb_width'):
            df['bb_width_pct'] = (upper - lower) / middle * 100
            df['bb_squeeze'] = (df['bb_width_pct'] < df['bb_width_pct'].rolling(20).mean()).astype(int)

        # Bollinger Band position
        df['bb_position'] = (df['close'] - lower) / (upper - lower + 0.0001)
        df['bb_above_upper'] = (df['close'] > upper).astype(int)
        df['bb_below_lower'] = (df['close'] < lower).astype(int)

    # ATR (Average True Range)
    if features.get('atr'):
        df['atr'] = calculate_atr(df, 14)
        df['atr_pct'] = df['atr'] / df['close'] * 100
        df['volatility_high'] = (df['atr_pct'] > df['atr_pct'].rolling(20).mean()).astype(int)

    # Stochastic Oscillator
    if features.get('stochastic_k') or features.get('stochastic_d'):
        period = 14
        low_min = df['low'].rolling(window=period).min()
        high_max = df['high'].rolling(window=period).max()

        stoch_k = 100 * (df['close'] - low_min) / (high_max - low_min + 0.0001)

        if features.get('stochastic_k'):
            df['stochastic_k'] = stoch_k

        if features.get('stochastic_d'):
            df['stochastic_d'] = stoch_k.rolling(window=3).mean()

    return df


def add_volume_indicators(df: pd.DataFrame, config: Dict) -> pd.DataFrame:
    """
    Add volume-based indicators

    Args:
        df: DataFrame with volume data
        config: Configuration dict

    Returns:
        DataFrame with volume indicators
    """
    features = config.get('features_enabled', {})

    if features.get('volume_ratio'):
        df['volume_sma_20'] = df['volume'].rolling(window=20).mean()
        df['volume_ratio'] = df['volume'] / (df['volume_sma_20'] + 1)
        df['volume_surge'] = (df['volume_ratio'] > 1.5).astype(int)
        df['volume_dry'] = (df['volume_ratio'] < 0.5).astype(int)

    if features.get('obv'):
        # On-Balance Volume
        obv = (np.sign(df['close'].diff()) * df['volume']).fillna(0).cumsum()
        df['obv'] = obv
        df['obv_sma'] = obv.rolling(window=20).mean()
        df['obv_increasing'] = (obv > obv.shift(1)).astype(int)

    return df


def add_multi_timeframe_features(df: pd.DataFrame) -> pd.DataFrame:
    """
    Add features from multiple timeframes

    Args:
        df: DataFrame with price data

    Returns:
        DataFrame with multi-timeframe features
    """
    # Short-term returns (momentum)
    df['return_1d'] = df['close'].pct_change(1) * 100
    df['return_2d'] = df['close'].pct_change(2) * 100
    df['return_3d'] = df['close'].pct_change(3) * 100
    df['return_5d'] = df['close'].pct_change(5) * 100

    # Medium-term returns (trend)
    df['return_10d'] = df['close'].pct_change(10) * 100
    df['return_20d'] = df['close'].pct_change(20) * 100

    # Volatility across timeframes
    df['volatility_5d'] = df['close'].pct_change().rolling(5).std() * 100
    df['volatility_20d'] = df['close'].pct_change().rolling(20).std() * 100

    # Trend strength
    df['trend_strength_10d'] = df['close'] / df['close'].rolling(10).mean()
    df['trend_strength_20d'] = df['close'] / df['close'].rolling(20).mean()

    # Price patterns
    df['higher_highs_3d'] = (df['high'] > df['high'].shift(1)).rolling(3).sum()
    df['lower_lows_3d'] = (df['low'] < df['low'].shift(1)).rolling(3).sum()

    # Consecutive up/down days
    df['consecutive_up'] = (df['close'] > df['close'].shift(1)).astype(int)
    df['consecutive_down'] = (df['close'] < df['close'].shift(1)).astype(int)

    return df


def add_time_features(df: pd.DataFrame) -> pd.DataFrame:
    """
    Add time-based features

    Markets behave differently on different days of the week

    Args:
        df: DataFrame with date column

    Returns:
        DataFrame with time features
    """
    df['date'] = pd.to_datetime(df['date'])
    df['day_of_week'] = df['date'].dt.dayofweek

    # Day of week dummies
    df['is_monday'] = (df['day_of_week'] == 0).astype(int)
    df['is_tuesday'] = (df['day_of_week'] == 1).astype(int)
    df['is_wednesday'] = (df['day_of_week'] == 2).astype(int)
    df['is_thursday'] = (df['day_of_week'] == 3).astype(int)
    df['is_friday'] = (df['day_of_week'] == 4).astype(int)

    # Week of month (1-5)
    df['week_of_month'] = (df['date'].dt.day - 1) // 7 + 1

    # Month (1-12)
    df['month'] = df['date'].dt.month

    return df


def create_target_variable(df: pd.DataFrame, target_type: str = 'open_to_close') -> pd.DataFrame:
    """
    Create target variable for prediction

    Args:
        df: DataFrame with price data
        target_type: Type of target to create
            - 'open_to_close': Predict if tomorrow closes above open (day trading)
            - 'close_to_close': Predict if tomorrow closes above today (traditional)
            - 'threshold': Predict if tomorrow gains > X% from open

    Returns:
        DataFrame with target column
    """
    if target_type == 'open_to_close':
        # Day trading target: Will tomorrow close above its open?
        df['target'] = (df['close'].shift(-1) > df['open'].shift(-1)).astype(int)

    elif target_type == 'close_to_close':
        # Traditional target: Will tomorrow close above today's close?
        df['target'] = (df['close'].shift(-1) > df['close']).astype(int)

    elif target_type == 'threshold':
        # Threshold target: Will tomorrow gain at least 1% from open to close?
        threshold = 0.01  # 1%
        tomorrow_return = (df['close'].shift(-1) - df['open'].shift(-1)) / df['open'].shift(-1)
        df['target'] = (tomorrow_return > threshold).astype(int)

    return df


def engineer_features(df: pd.DataFrame, config: Dict) -> pd.DataFrame:
    """
    Main feature engineering function

    Orchestrates all feature engineering steps for day trading

    Args:
        df: DataFrame with OHLCV data (columns: date, open, high, low, close, volume)
        config: Configuration dictionary with:
            - features_enabled: Dict of which features to calculate
            - target_type: Type of target variable

    Returns:
        DataFrame with all engineered features and target variable
    """
    print(f"Starting feature engineering...")
    print(f"Input shape: {df.shape}")

    # Make a copy to avoid modifying original
    df = df.copy()

    # Sort by date to ensure proper time series order
    df = df.sort_values('date').reset_index(drop=True)

    # 1. Add intraday features (MOST IMPORTANT FOR DAY TRADING!)
    print("Adding intraday features...")
    df = add_intraday_features(df)

    # 2. Add technical indicators
    print("Adding technical indicators...")
    df = add_technical_indicators(df, config)

    # 3. Add volume indicators
    print("Adding volume indicators...")
    df = add_volume_indicators(df, config)

    # 4. Add multi-timeframe features
    print("Adding multi-timeframe features...")
    df = add_multi_timeframe_features(df)

    # 5. Add time-based features
    print("Adding time features...")
    df = add_time_features(df)

    # 6. Create target variable
    print("Creating target variable...")
    target_type = config.get('target_type', 'open_to_close')
    df = create_target_variable(df, target_type)

    # 7. Remove rows with NaN (from rolling calculations)
    initial_rows = len(df)
    df = df.dropna()
    rows_dropped = initial_rows - len(df)

    print(f"Dropped {rows_dropped} rows with NaN values")
    print(f"Final shape: {df.shape}")
    print(f"Features created: {df.shape[1] - 6}")  # Subtract OHLCV + date

    return df


def get_feature_list(df: pd.DataFrame, exclude_cols: List[str] = None) -> List[str]:
    """
    Get list of feature columns (excluding OHLCV, date, target)

    Args:
        df: DataFrame with features
        exclude_cols: Additional columns to exclude

    Returns:
        List of feature column names
    """
    if exclude_cols is None:
        exclude_cols = []

    exclude = ['date', 'open', 'high', 'low', 'close', 'volume', 'target'] + exclude_cols

    features = [col for col in df.columns if col not in exclude]

    return features


def get_feature_importance_report(model, feature_names: List[str], top_n: int = 20) -> pd.DataFrame:
    """
    Get feature importance from trained model

    Args:
        model: Trained XGBoost model
        feature_names: List of feature names
        top_n: Number of top features to return

    Returns:
        DataFrame with feature importance
    """
    importance = model.feature_importances_

    feature_importance = pd.DataFrame({
        'feature': feature_names,
        'importance': importance
    }).sort_values('importance', ascending=False)

    return feature_importance.head(top_n)


if __name__ == '__main__':
    """
    Test the feature engineering pipeline
    """
    # Create sample data for testing
    dates = pd.date_range('2023-01-01', periods=300, freq='D')

    sample_data = pd.DataFrame({
        'date': dates,
        'open': np.random.randn(300).cumsum() + 100,
        'high': np.random.randn(300).cumsum() + 102,
        'low': np.random.randn(300).cumsum() + 98,
        'close': np.random.randn(300).cumsum() + 100,
        'volume': np.random.randint(1000000, 10000000, 300)
    })

    # Sample configuration
    config = {
        'features_enabled': {
            'sma_10': True,
            'sma_50': True,
            'rsi_14': True,
            'macd': True,
            'bb_width': True,
            'atr': True,
            'volume_ratio': True,
        },
        'target_type': 'open_to_close'
    }

    # Engineer features
    engineered_df = engineer_features(sample_data, config)

    # Print summary
    print("\n" + "="*60)
    print("FEATURE ENGINEERING SUMMARY")
    print("="*60)
    print(f"Total features: {len(get_feature_list(engineered_df))}")
    print(f"Usable rows: {len(engineered_df)}")
    print(f"\nTarget distribution:")
    print(engineered_df['target'].value_counts())
    print(f"\nSample features:")
    print(get_feature_list(engineered_df)[:20])
