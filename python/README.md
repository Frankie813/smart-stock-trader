# Python ML Setup Instructions

This directory contains the Python machine learning layer for the stock prediction system.

## Setup

1. **Create virtual environment:**
   ```bash
   cd python
   python3 -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

2. **Install dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

## Directory Structure

```
python/
├── venv/                   # Virtual environment (excluded from git)
├── models/                 # Trained .pkl model files (excluded from git)
├── data/                   # Temporary CSV exports (excluded from git)
├── requirements.txt        # Python dependencies
├── config.py              # ML configuration settings
├── utils.py               # Helper functions
├── feature_engineering.py # Technical indicators calculation
├── train_model.py         # Model training script
└── backtest.py            # Trading simulation script
```

## Files Created

All Python scripts have been created with production-ready code including:
- Comprehensive error handling
- Logging
- JSON output for Laravel integration
- 50+ technical indicators
- XGBoost training with feature importance
- Trading simulation with performance metrics

## Usage

Train a model:
```bash
python/venv/bin/python python/train_model.py AAPL
```

Run backtest:
```bash
python/venv/bin/python python/backtest.py AAPL
```

Or use the Laravel Artisan commands:
```bash
php artisan train:model AAPL
```
