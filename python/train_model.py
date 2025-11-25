#!/usr/bin/env python3
"""
Train XGBoost model for stock price prediction

Usage: python train_model.py AAPL
"""

import sys
import logging
from datetime import datetime
from pathlib import Path

import pandas as pd
import numpy as np
import joblib
from sklearn.model_selection import train_test_split
from xgboost import XGBClassifier

import config
from feature_engineering import engineer_features, get_feature_columns
from utils import (
    logger,
    load_data_from_csv,
    calculate_metrics,
    save_results,
    handle_error,
    validate_dataframe
)

# Configure logging
logger = logging.getLogger(__name__)


def prepare_data(symbol: str):
    """
    Load and prepare data for training

    Args:
        symbol: Stock symbol

    Returns:
        Tuple of (X_train, X_test, y_train, y_test, feature_names, df_with_features)
    """
    logger.info(f"Loading data for {symbol}")

    # Load raw data
    df = load_data_from_csv(symbol)

    # Validate minimum samples
    if len(df) < config.MIN_TRAINING_SAMPLES:
        raise ValueError(
            f"Insufficient data: {len(df)} rows. Minimum required: {config.MIN_TRAINING_SAMPLES}"
        )

    logger.info(f"Loaded {len(df)} rows of data")

    # Engineer features
    df_with_features = engineer_features(df, include_target=True)

    logger.info(f"After feature engineering: {len(df_with_features)} rows")

    # Get feature columns
    feature_cols = get_feature_columns(df_with_features)

    # Prepare X (features) and y (target)
    X = df_with_features[feature_cols].values
    y = df_with_features['target'].values

    logger.info(f"Features shape: {X.shape}, Target shape: {y.shape}")

    # Split into train and test sets
    X_train, X_test, y_train, y_test = train_test_split(
        X, y,
        test_size=config.TEST_SIZE,
        random_state=config.RANDOM_STATE,
        shuffle=False  # Maintain temporal order for time series
    )

    logger.info(f"Train set: {X_train.shape}, Test set: {X_test.shape}")
    logger.info(f"Train positive rate: {y_train.mean():.2%}, Test positive rate: {y_test.mean():.2%}")

    return X_train, X_test, y_train, y_test, feature_cols, df_with_features


def train_xgboost_model(X_train, y_train, X_test, y_test):
    """
    Train XGBoost classifier

    Args:
        X_train: Training features
        y_train: Training target
        X_test: Test features
        y_test: Test target

    Returns:
        Trained XGBoost model
    """
    logger.info("Training XGBoost model...")

    # Initialize model
    model = XGBClassifier(**config.XGBOOST_PARAMS)

    # Train model
    model.fit(
        X_train, y_train,
        eval_set=[(X_test, y_test)],
        verbose=False
    )

    logger.info("Model training complete")

    return model


def evaluate_model(model, X_train, y_train, X_test, y_test):
    """
    Evaluate model performance

    Args:
        model: Trained model
        X_train: Training features
        y_train: Training target
        X_test: Test features
        y_test: Test target

    Returns:
        Dictionary of evaluation metrics
    """
    logger.info("Evaluating model...")

    # Predictions
    y_train_pred = model.predict(X_train)
    y_test_pred = model.predict(X_test)

    y_train_pred_proba = model.predict_proba(X_train)[:, 1]
    y_test_pred_proba = model.predict_proba(X_test)[:, 1]

    # Calculate metrics
    train_metrics = calculate_metrics(y_train, y_train_pred, y_train_pred_proba)
    test_metrics = calculate_metrics(y_test, y_test_pred, y_test_pred_proba)

    logger.info(f"Train Accuracy: {train_metrics['accuracy']:.4f}")
    logger.info(f"Test Accuracy: {test_metrics['accuracy']:.4f}")
    logger.info(f"Test Precision: {test_metrics['precision']:.4f}")
    logger.info(f"Test Recall: {test_metrics['recall']:.4f}")
    logger.info(f"Test F1 Score: {test_metrics['f1_score']:.4f}")

    return {
        'train_metrics': train_metrics,
        'test_metrics': test_metrics,
    }


def get_feature_importance(model, feature_names):
    """
    Get feature importance from trained model

    Args:
        model: Trained XGBoost model
        feature_names: List of feature names

    Returns:
        Dictionary of feature importance scores
    """
    importance_scores = model.feature_importances_

    # Create feature importance dictionary
    feature_importance = {
        name: float(score)
        for name, score in zip(feature_names, importance_scores)
    }

    # Sort by importance
    feature_importance = dict(
        sorted(feature_importance.items(), key=lambda x: x[1], reverse=True)
    )

    # Log top 10 features
    logger.info("Top 10 most important features:")
    for i, (feature, score) in enumerate(list(feature_importance.items())[:10], 1):
        logger.info(f"  {i}. {feature}: {score:.4f}")

    return feature_importance


def save_model(model, symbol: str, metadata: dict):
    """
    Save trained model and metadata

    Args:
        model: Trained model
        symbol: Stock symbol
        metadata: Model metadata

    Returns:
        Path to saved model file
    """
    model_filename = f"{symbol}_model.pkl"
    model_path = config.MODELS_DIR / model_filename

    # Add metadata
    model_data = {
        'model': model,
        'symbol': symbol,
        'trained_at': datetime.now().isoformat(),
        'version': config.MODEL_VERSION,
        'metadata': metadata,
    }

    # Save model
    joblib.dump(model_data, model_path)

    logger.info(f"Model saved to {model_path}")

    return str(model_path)


def main(symbol: str):
    """
    Main training pipeline

    Args:
        symbol: Stock symbol

    Returns:
        Dictionary of training results
    """
    try:
        # Prepare data
        X_train, X_test, y_train, y_test, feature_names, df_with_features = prepare_data(symbol)

        # Train model
        model = train_xgboost_model(X_train, y_train, X_test, y_test)

        # Evaluate model
        evaluation = evaluate_model(model, X_train, y_train, X_test, y_test)

        # Get feature importance
        feature_importance = get_feature_importance(model, feature_names)

        # Prepare metadata
        metadata = {
            'train_samples': int(len(X_train)),
            'test_samples': int(len(X_test)),
            'num_features': int(len(feature_names)),
            'train_accuracy': evaluation['train_metrics']['accuracy'],
            'test_accuracy': evaluation['test_metrics']['accuracy'],
            'feature_names': feature_names,
        }

        # Save model
        model_path = save_model(model, symbol, metadata)

        # Prepare results
        result = {
            'success': True,
            'symbol': symbol,
            'model_path': model_path,
            'model_version': config.MODEL_VERSION,
            'trained_at': datetime.now().isoformat(),
            'data_summary': {
                'total_samples': int(len(df_with_features)),
                'train_samples': int(len(X_train)),
                'test_samples': int(len(X_test)),
                'num_features': int(len(feature_names)),
            },
            'train_metrics': evaluation['train_metrics'],
            'test_metrics': evaluation['test_metrics'],
            'feature_importance': dict(list(feature_importance.items())[:10]),  # Top 10
        }

        logger.info("Training pipeline complete")

        return result

    except Exception as e:
        return handle_error(e, "TRAINING_ERROR")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python train_model.py <SYMBOL>")
        print("Example: python train_model.py AAPL")
        sys.exit(1)

    symbol = sys.argv[1].upper()

    # Run training
    result = main(symbol)

    # Output results as JSON
    save_results(result)

    # Exit with appropriate code
    sys.exit(0 if result.get('success', False) else 1)
