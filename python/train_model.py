"""
Model Training Script for Day Trading
Trains XGBoost model with enhanced features
"""

import sys
import json
import pandas as pd
import numpy as np
import xgboost as xgb
import joblib
from datetime import datetime
from pathlib import Path
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix

# Import feature engineering
from feature_engineering import engineer_features, get_feature_list, get_feature_importance_report


def load_stock_data(stock_symbol: str, data_path: str = None) -> pd.DataFrame:
    """
    Load stock price data from CSV

    Args:
        stock_symbol: Stock ticker symbol
        data_path: Path to data directory

    Returns:
        DataFrame with OHLCV data
    """
    if data_path is None:
        data_path = Path(__file__).parent / 'data'

    csv_file = Path(data_path) / f'{stock_symbol}.csv'

    if not csv_file.exists():
        raise FileNotFoundError(f"Data file not found: {csv_file}")

    df = pd.read_csv(csv_file)

    # Ensure required columns exist
    required_cols = ['date', 'open', 'high', 'low', 'close', 'volume']
    missing_cols = [col for col in required_cols if col not in df.columns]

    if missing_cols:
        raise ValueError(f"Missing required columns: {missing_cols}")

    return df


def prepare_training_data(df: pd.DataFrame, config: dict, train_size: float = 0.8):
    """
    Prepare data for training

    Args:
        df: DataFrame with engineered features
        config: Configuration dictionary
        train_size: Proportion of data for training (0.8 = 80%)

    Returns:
        Tuple of (X_train, X_test, y_train, y_test, feature_names)
    """
    # Get feature columns (exclude OHLCV, date, target)
    feature_cols = get_feature_list(df)

    # Separate features and target
    X = df[feature_cols].values
    y = df['target'].values
    dates = df['date'].values

    # Split by time (not random!)
    # We want to train on old data, test on recent data
    split_idx = int(len(df) * train_size)

    X_train = X[:split_idx]
    X_test = X[split_idx:]
    y_train = y[:split_idx]
    y_test = y[split_idx:]

    train_dates = dates[:split_idx]
    test_dates = dates[split_idx:]

    print(f"\nTraining set: {len(X_train)} samples ({train_dates[0]} to {train_dates[-1]})")
    print(f"Test set: {len(X_test)} samples ({test_dates[0]} to {test_dates[-1]})")
    print(f"Features: {len(feature_cols)}")
    print(f"\nTarget distribution (train): {np.bincount(y_train)}")
    print(f"Target distribution (test): {np.bincount(y_test)}")

    return X_train, X_test, y_train, y_test, feature_cols


def train_xgboost_model(X_train, y_train, X_test, y_test, hyperparameters: dict):
    """
    Train XGBoost classifier

    Args:
        X_train, y_train: Training data
        X_test, y_test: Test data
        hyperparameters: Model hyperparameters

    Returns:
        Trained model
    """
    print("\n" + "="*60)
    print("TRAINING XGBOOST MODEL")
    print("="*60)

    # Create model with hyperparameters from configuration
    model = xgb.XGBClassifier(
        n_estimators=hyperparameters.get('n_estimators', 100),
        max_depth=hyperparameters.get('max_depth', 5),
        learning_rate=hyperparameters.get('learning_rate', 0.1),
        subsample=hyperparameters.get('subsample', 0.8),
        colsample_bytree=hyperparameters.get('colsample_bytree', 0.8),
        min_child_weight=hyperparameters.get('min_child_weight', 1),
        gamma=hyperparameters.get('gamma', 0),
        objective='binary:logistic',
        eval_metric='logloss',
        random_state=42,
        n_jobs=-1
    )

    # Train with early stopping
    model.fit(
        X_train,
        y_train,
        eval_set=[(X_train, y_train), (X_test, y_test)],
        verbose=False
    )

    # Evaluate
    train_pred = model.predict(X_train)
    test_pred = model.predict(X_test)

    train_accuracy = accuracy_score(y_train, train_pred)
    test_accuracy = accuracy_score(y_test, test_pred)

    print(f"\nTraining Accuracy: {train_accuracy:.4f} ({train_accuracy*100:.2f}%)")
    print(f"Test Accuracy: {test_accuracy:.4f} ({test_accuracy*100:.2f}%)")

    # Check for overfitting
    if train_accuracy - test_accuracy > 0.1:
        print("\n⚠️  WARNING: Possible overfitting detected!")
        print(f"   Gap between train and test: {(train_accuracy - test_accuracy)*100:.2f}%")

    # Confusion matrix
    print(f"\nConfusion Matrix (Test Set):")
    cm = confusion_matrix(y_test, test_pred)
    print(cm)
    print(f"\nTrue Negatives: {cm[0,0]}, False Positives: {cm[0,1]}")
    print(f"False Negatives: {cm[1,0]}, True Positives: {cm[1,1]}")

    return model


def save_model(model, stock_symbol: str, metadata: dict, model_path: str = None):
    """
    Save trained model and metadata

    Args:
        model: Trained XGBoost model
        stock_symbol: Stock ticker
        metadata: Dictionary with training info
        model_path: Path to save model

    Returns:
        Path where model was saved
    """
    if model_path is None:
        model_path = Path(__file__).parent / 'models'

    model_path = Path(model_path)
    model_path.mkdir(parents=True, exist_ok=True)

    # Save model
    model_file = model_path / f'{stock_symbol}_model.pkl'
    joblib.dump(model, model_file)

    # Save metadata
    metadata_file = model_path / f'{stock_symbol}_metadata.json'
    with open(metadata_file, 'w') as f:
        json.dump(metadata, f, indent=2, default=str)

    print(f"\n✓ Model saved: {model_file}")
    print(f"✓ Metadata saved: {metadata_file}")

    return str(model_file)


def train_model(stock_symbol: str, config_json: str):
    """
    Main training function

    Args:
        stock_symbol: Stock ticker symbol
        config_json: JSON string with configuration

    Returns:
        Dictionary with training results
    """
    try:
        # Parse configuration
        config = json.loads(config_json)

        print("\n" + "="*60)
        print(f"TRAINING MODEL FOR {stock_symbol}")
        print("="*60)
        print(f"Configuration: {config.get('name', 'Custom')}")

        # 1. Load data
        print("\n[1/5] Loading data...")
        df = load_stock_data(stock_symbol)
        print(f"Loaded {len(df)} days of data")

        # 2. Engineer features
        print("\n[2/5] Engineering features...")
        df_features = engineer_features(df, config)
        print(f"Created {df_features.shape[1] - 6} features")

        # 3. Prepare training data
        print("\n[3/5] Preparing training data...")
        train_split = config['hyperparameters'].get('train_test_split', 0.8)
        X_train, X_test, y_train, y_test, feature_names = prepare_training_data(
            df_features,
            config,
            train_size=train_split
        )

        # 4. Train model
        print("\n[4/5] Training model...")
        model = train_xgboost_model(
            X_train, y_train,
            X_test, y_test,
            config['hyperparameters']
        )

        # Get feature importance
        feature_importance_df = get_feature_importance_report(model, feature_names, top_n=20)

        print(f"\nTop 10 Most Important Features:")
        for idx, row in feature_importance_df.head(10).iterrows():
            print(f"  {row['feature']:25s}: {row['importance']:.4f}")

        # 5. Save model
        print("\n[5/5] Saving model...")

        # Calculate additional metrics
        test_pred = model.predict(X_test)
        test_pred_proba = model.predict_proba(X_test)

        # Calculate prediction confidence stats
        confidence_scores = test_pred_proba.max(axis=1)
        avg_confidence = confidence_scores.mean()

        # Prepare metadata
        metadata = {
            'stock_symbol': stock_symbol,
            'trained_at': datetime.now().isoformat(),
            'model_version': '2.0_daytrading',
            'train_size': len(X_train),
            'test_size': len(X_test),
            'train_accuracy': float(accuracy_score(y_train, model.predict(X_train))),
            'test_accuracy': float(accuracy_score(y_test, test_pred)),
            'avg_confidence': float(avg_confidence),
            'num_features': len(feature_names),
            'features_used': feature_names,
            'feature_importance': feature_importance_df.to_dict('records'),
            'hyperparameters': config['hyperparameters'],
            'features_enabled': config.get('features_enabled', {}),
            'target_type': config.get('target_type', 'open_to_close'),
            'target_distribution_test': {
                'down': int(np.sum(y_test == 0)),
                'up': int(np.sum(y_test == 1))
            }
        }

        model_path = save_model(model, stock_symbol, metadata)

        # Prepare results for Laravel
        results = {
            'success': True,
            'stock_symbol': stock_symbol,
            'model_path': model_path,
            'train_accuracy': metadata['train_accuracy'],
            'test_accuracy': metadata['test_accuracy'],
            'avg_confidence': metadata['avg_confidence'],
            'num_features': len(feature_names),
            'top_features': feature_importance_df.head(10).to_dict('records'),
            'trained_at': metadata['trained_at']
        }

        print("\n" + "="*60)
        print("✓ TRAINING COMPLETE")
        print("="*60)
        print(f"Model: {stock_symbol}_model.pkl")
        print(f"Test Accuracy: {results['test_accuracy']*100:.2f}%")
        print(f"Avg Confidence: {results['avg_confidence']*100:.2f}%")

        # Return as JSON for Laravel to parse
        return results

    except Exception as e:
        print(f"\n❌ ERROR during training: {str(e)}")
        import traceback
        traceback.print_exc()

        return {
            'success': False,
            'error': str(e),
            'stock_symbol': stock_symbol
        }


if __name__ == '__main__':
    """
    Command line interface
    Usage: python train_model.py AAPL '{"hyperparameters": {...}, "features_enabled": {...}}'
    """
    if len(sys.argv) < 3:
        print("Usage: python train_model.py STOCK_SYMBOL CONFIG_JSON")
        print("Example: python train_model.py AAPL '{\"hyperparameters\": {\"n_estimators\": 100}}'")
        sys.exit(1)

    stock_symbol = sys.argv[1]
    config_json = sys.argv[2]

    # Train model
    results = train_model(stock_symbol, config_json)

    # Print results as JSON (Laravel will parse this)
    print("\n" + "="*60)
    print("RESULTS (JSON)")
    print("="*60)
    print(json.dumps(results, indent=2))
