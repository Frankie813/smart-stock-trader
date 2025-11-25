"""
Feature engineering for stock price prediction
Calculates technical indicators from OHLCV data
"""

import sys
import logging
from typing import Optional

import pandas as pd
import numpy as np
from ta.trend import SMAIndicator, EMAIndicator, MACD, ADXIndicator
from ta.momentum import RSIIndicator, StochasticOscillator, ROCIndicator
from ta.volatility import BollingerBands, AverageTrueRange
from ta.volume import OnBalanceVolumeIndicator, VolumePriceTrendIndicator

from config import TECHNICAL_INDICATORS, REQUIRED_COLUMNS
from utils import logger, validate_dataframe

# Configure logging
logger = logging.getLogger(__name__)


def add_moving_averages(df: pd.DataFrame) -> pd.DataFrame:
    """Add Simple Moving Averages (SMA) and Exponential Moving Averages (EMA)"""
    close = df['close']

    # Simple Moving Averages
    for period in TECHNICAL_INDICATORS['sma_periods']:
        indicator = SMAIndicator(close=close, window=period, fillna=True)
        df[f'sma_{period}'] = indicator.sma_indicator()

    # Exponential Moving Averages
    for period in TECHNICAL_INDICATORS['ema_periods']:
        indicator = EMAIndicator(close=close, window=period, fillna=True)
        df[f'ema_{period}'] = indicator.ema_indicator()

    logger.info("Added moving averages")
    return df


def add_rsi(df: pd.DataFrame) -> pd.DataFrame:
    """Add Relative Strength Index (RSI) for multiple periods"""
    close = df['close']

    for period in TECHNICAL_INDICATORS['rsi_periods']:
        indicator = RSIIndicator(close=close, window=period, fillna=True)
        df[f'rsi_{period}'] = indicator.rsi()

    logger.info("Added RSI indicators")
    return df


def add_macd(df: pd.DataFrame) -> pd.DataFrame:
    """Add MACD (Moving Average Convergence Divergence)"""
    close = df['close']

    macd = MACD(
        close=close,
        window_fast=TECHNICAL_INDICATORS['macd_fast'],
        window_slow=TECHNICAL_INDICATORS['macd_slow'],
        window_sign=TECHNICAL_INDICATORS['macd_signal'],
        fillna=True
    )

    df['macd'] = macd.macd()
    df['macd_signal'] = macd.macd_signal()
    df['macd_diff'] = macd.macd_diff()

    logger.info("Added MACD indicators")
    return df


def add_bollinger_bands(df: pd.DataFrame) -> pd.DataFrame:
    """Add Bollinger Bands"""
    close = df['close']

    bb = BollingerBands(
        close=close,
        window=TECHNICAL_INDICATORS['bb_period'],
        window_dev=TECHNICAL_INDICATORS['bb_std'],
        fillna=True
    )

    df['bb_high'] = bb.bollinger_hband()
    df['bb_mid'] = bb.bollinger_mavg()
    df['bb_low'] = bb.bollinger_lband()
    df['bb_width'] = bb.bollinger_wband()
    df['bb_pct'] = bb.bollinger_pband()

    logger.info("Added Bollinger Bands")
    return df


def add_atr(df: pd.DataFrame) -> pd.DataFrame:
    """Add Average True Range (ATR)"""
    atr = AverageTrueRange(
        high=df['high'],
        low=df['low'],
        close=df['close'],
        window=TECHNICAL_INDICATORS['atr_period'],
        fillna=True
    )

    df['atr'] = atr.average_true_range()

    logger.info("Added ATR")
    return df


def add_adx(df: pd.DataFrame) -> pd.DataFrame:
    """Add Average Directional Index (ADX)"""
    adx = ADXIndicator(
        high=df['high'],
        low=df['low'],
        close=df['close'],
        window=TECHNICAL_INDICATORS['adx_period'],
        fillna=True
    )

    df['adx'] = adx.adx()
    df['adx_pos'] = adx.adx_pos()
    df['adx_neg'] = adx.adx_neg()

    logger.info("Added ADX")
    return df


def add_stochastic_oscillator(df: pd.DataFrame) -> pd.DataFrame:
    """Add Stochastic Oscillator"""
    stoch = StochasticOscillator(
        high=df['high'],
        low=df['low'],
        close=df['close'],
        window=14,
        smooth_window=3,
        fillna=True
    )

    df['stoch_k'] = stoch.stoch()
    df['stoch_d'] = stoch.stoch_signal()

    logger.info("Added Stochastic Oscillator")
    return df


def add_roc(df: pd.DataFrame) -> pd.DataFrame:
    """Add Rate of Change (ROC)"""
    roc = ROCIndicator(close=df['close'], window=12, fillna=True)
    df['roc'] = roc.roc()

    logger.info("Added ROC")
    return df


def add_volume_indicators(df: pd.DataFrame) -> pd.DataFrame:
    """Add volume-based indicators"""
    # Volume change rate
    df['volume_change'] = df['volume'].pct_change().fillna(0)

    # Volume moving average (20-day)
    df['volume_sma_20'] = df['volume'].rolling(window=20).mean().fillna(method='bfill')

    # On-Balance Volume (OBV)
    obv = OnBalanceVolumeIndicator(close=df['close'], volume=df['volume'], fillna=True)
    df['obv'] = obv.on_balance_volume()

    # Volume Price Trend
    vpt = VolumePriceTrendIndicator(close=df['close'], volume=df['volume'], fillna=True)
    df['vpt'] = vpt.volume_price_trend()

    logger.info("Added volume indicators")
    return df


def add_price_features(df: pd.DataFrame) -> pd.DataFrame:
    """Add price-based features"""
    # Daily return
    df['daily_return'] = df['close'].pct_change().fillna(0)

    # High-Low range
    df['high_low_range'] = (df['high'] - df['low']) / df['close']

    # Gap (difference between open and previous close)
    df['gap'] = (df['open'] - df['close'].shift(1)) / df['close'].shift(1)
    df['gap'] = df['gap'].fillna(0)

    # Price momentum (5-day)
    df['momentum_5'] = df['close'].diff(5).fillna(0)

    # Close position within high-low range
    df['close_position'] = (df['close'] - df['low']) / (df['high'] - df['low'])
    df['close_position'] = df['close_position'].fillna(0.5)

    logger.info("Added price features")
    return df


def add_target_variable(df: pd.DataFrame) -> pd.DataFrame:
    """
    Create target variable: 1 if next day close is higher than today's close, 0 otherwise
    """
    df['next_close'] = df['close'].shift(-1)
    df['target'] = (df['next_close'] > df['close']).astype(int)

    # Remove the last row (no next day data)
    df = df[:-1].copy()

    logger.info("Added target variable")
    return df


def engineer_features(df: pd.DataFrame, include_target: bool = True) -> pd.DataFrame:
    """
    Apply all feature engineering steps

    Args:
        df: DataFrame with OHLCV data
        include_target: Whether to include target variable (use False for prediction)

    Returns:
        DataFrame with engineered features
    """
    logger.info("Starting feature engineering...")

    # Validate input data
    validate_dataframe(df, REQUIRED_COLUMNS)

    # Make a copy to avoid modifying original
    df = df.copy()

    # Sort by date
    if 'date' in df.columns:
        df = df.sort_values('date').reset_index(drop=True)

    # Add all features
    df = add_moving_averages(df)
    df = add_rsi(df)
    df = add_macd(df)
    df = add_bollinger_bands(df)
    df = add_atr(df)
    df = add_adx(df)
    df = add_stochastic_oscillator(df)
    df = add_roc(df)
    df = add_volume_indicators(df)
    df = add_price_features(df)

    # Add target variable if requested
    if include_target:
        df = add_target_variable(df)

    # Drop any remaining NaN values
    initial_count = len(df)
    df = df.dropna()
    final_count = len(df)

    if initial_count - final_count > 0:
        logger.warning(f"Dropped {initial_count - final_count} rows with NaN values")

    logger.info(f"Feature engineering complete. Final shape: {df.shape}")

    return df


def get_feature_columns(df: pd.DataFrame) -> list:
    """
    Get list of feature columns (excluding date, OHLCV, and target)

    Args:
        df: DataFrame with engineered features

    Returns:
        List of feature column names
    """
    exclude_cols = ['date', 'open', 'high', 'low', 'close', 'volume',
                    'adjusted_close', 'target', 'next_close', 'stock_id',
                    'id', 'created_at', 'updated_at']

    feature_cols = [col for col in df.columns if col not in exclude_cols]

    logger.info(f"Found {len(feature_cols)} feature columns")

    return feature_cols


if __name__ == "__main__":
    """
    Standalone usage: python feature_engineering.py AAPL
    """
    if len(sys.argv) < 2:
        print("Usage: python feature_engineering.py <SYMBOL>")
        sys.exit(1)

    symbol = sys.argv[1].upper()

    try:
        from utils import load_data_from_csv, save_results
        import config

        # Load data
        df = load_data_from_csv(symbol)

        # Engineer features
        df_with_features = engineer_features(df, include_target=True)

        # Save to CSV
        output_file = f"{symbol}_features.csv"
        output_path = config.DATA_DIR / output_file
        df_with_features.to_csv(output_path, index=False)

        # Return success
        result = {
            'success': True,
            'symbol': symbol,
            'rows': len(df_with_features),
            'features': len(get_feature_columns(df_with_features)),
            'output_file': str(output_path),
        }

        save_results(result)

    except Exception as e:
        from utils import handle_error
        error_result = handle_error(e, "FEATURE_ENGINEERING_ERROR")
        save_results(error_result)
        sys.exit(1)
