"""
Configuration settings for the ML training pipeline
"""

import os
from pathlib import Path

# Base directory
BASE_DIR = Path(__file__).parent

# Data directories
DATA_DIR = BASE_DIR / 'data'
MODELS_DIR = BASE_DIR / 'models'

# Ensure directories exist
DATA_DIR.mkdir(exist_ok=True)
MODELS_DIR.mkdir(exist_ok=True)

# XGBoost hyperparameters
XGBOOST_PARAMS = {
    'objective': 'binary:logistic',
    'max_depth': 6,
    'learning_rate': 0.1,
    'n_estimators': 100,
    'subsample': 0.8,
    'colsample_bytree': 0.8,
    'gamma': 0.1,
    'min_child_weight': 1,
    'random_state': 42,
    'eval_metric': 'logloss',
}

# Train/test split ratio
TEST_SIZE = 0.2
RANDOM_STATE = 42

# Technical indicator parameters
TECHNICAL_INDICATORS = {
    'sma_periods': [10, 20, 50, 200],
    'ema_periods': [12, 26],
    'rsi_periods': [7, 14, 21],
    'bb_period': 20,
    'bb_std': 2,
    'macd_fast': 12,
    'macd_slow': 26,
    'macd_signal': 9,
    'atr_period': 14,
    'adx_period': 14,
}

# Feature engineering settings
REQUIRED_COLUMNS = ['date', 'open', 'high', 'low', 'close', 'volume']

# Minimum number of data points required for training
MIN_TRAINING_SAMPLES = 100

# Model versioning
MODEL_VERSION = '1.0.0'

# Logging
LOG_LEVEL = 'INFO'
