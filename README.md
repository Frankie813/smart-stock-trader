# 🤖 ML Stock Trading Bot - Proof of Concept

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Python 3.10+](https://img.shields.io/badge/python-3.10+-blue.svg)](https://www.python.org/downloads/)
[![Laravel 11](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![XGBoost](https://img.shields.io/badge/XGBoost-2.0.3-brightgreen.svg)](https://xgboost.readthedocs.io/)

A production-ready machine learning trading bot that uses XGBoost to predict intraday stock movements. Built as a proof of concept for backtesting with historical data before live trading deployment.

**⚠️ DISCLAIMER:** This is educational software for backtesting only. Past performance does not guarantee future results. Trading stocks involves substantial risk. Use at your own risk.

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
- [How It Works](#-how-it-works)
- [Technology Stack](#-technology-stack)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [Configuration](#-configuration)
- [Usage Guide](#-usage-guide)
- [Understanding the Metrics](#-understanding-the-metrics)
- [Performance Expectations](#-performance-expectations)
- [Project Structure](#-project-structure)
- [API Reference](#-api-reference)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [Roadmap](#-roadmap)
- [License](#-license)
- [Acknowledgments](#-acknowledgments)

---

## 🎯 Overview

This project demonstrates a machine learning approach to day trading stocks using technical analysis and supervised learning. The system:

1. **Fetches** historical stock data (2+ years of daily OHLCV data)
2. **Engineers** 60+ technical features (RSI, MACD, Bollinger Bands, intraday patterns, etc.)
3. **Trains** XGBoost models to predict intraday movements (open-to-close predictions)
4. **Backtests** trading strategies with realistic execution (stop losses, take profits, commissions)
5. **Evaluates** profitability using industry-standard metrics (Sharpe ratio, max drawdown, profit factor)

**Primary Goal:** Validate whether machine learning can consistently predict profitable day trading opportunities before deploying with real capital.

---

## ✨ Key Features

### 🧠 Machine Learning
- **XGBoost Classifier** with optimized hyperparameters
- **60+ engineered features** including intraday patterns, technical indicators, volume analysis, and time-based features
- **Open-to-close prediction target** (predicts actual tradable intraday movement)
- **Confidence-based filtering** (only trades high-confidence predictions)
- **Per-stock specialized models** (each stock gets its own trained model)

### 📊 Comprehensive Backtesting
- **Realistic trade simulation** with entry/exit logic
- **Stop loss and take profit** execution
- **Commission modeling** ($1 per trade default)
- **Daily loss limits** and consecutive loss protection
- **Position sizing** and risk management
- **Detailed trade history** with exit reasons

### 📈 Professional Metrics
- **Win Rate** - Percentage of profitable trades
- **Profit Factor** - Gross profit / gross loss ratio
- **Sharpe Ratio** - Risk-adjusted returns
- **Maximum Drawdown** - Largest peak-to-valley decline
- **Total Return** - Overall percentage gain/loss
- **Prediction Accuracy** - Model correctness rate

### 🎨 Modern Tech Stack
- **Laravel 11** - Backend framework and API
- **Livewire 3** - Dynamic UI components (optional)
- **Python 3.10+** - ML pipeline and data processing
- **XGBoost 2.0** - Gradient boosting model
- **MySQL/PostgreSQL** - Relational database
- **Flux Pro** - UI component library (optional)

### 🔧 Developer Friendly
- **Artisan commands** for training and backtesting
- **Modular architecture** - Easy to extend and customize
- **Comprehensive documentation** - Every function explained
- **Test suite** - Validates entire pipeline
- **Error handling** - Detailed logging and debugging

---

## 🔍 How It Works

### The Strategy (Simplified)

```
1. Before Market Open (9:00 AM):
   → Model predicts: "Will stock close above its open today?"
   → Confidence check: Is model >65% confident?
   → Decision: Enter trade or skip

2. Market Opens (9:30 AM):
   → If prediction is UP and high confidence: BUY at open
   → Set stop loss: -2% (exit if drops)
   → Set take profit: +4% (exit if rises)

3. During Trading Day:
   → Monitor for stop loss hit → Exit immediately
   → Monitor for take profit hit → Exit immediately
   → Otherwise hold position

4. End of Day (4:00 PM):
   → Exit any remaining positions at close
   → Record profit/loss
   → Repeat next day
```

### The Innovation

**Traditional ML trading bots predict:** "Will tomorrow's close be higher than today's close?"
- ❌ Problem: You can't trade on that prediction! You buy at tomorrow's open, not today's close.

**Our bot predicts:** "Will tomorrow's close be higher than tomorrow's open?"
- ✅ Solution: This matches actual day trading! You buy at open and sell at close.

This alignment between prediction and trading reality is critical for profitability.

---

## 🛠 Technology Stack

### Backend (Laravel)
- **Laravel 11** - Application framework
- **MySQL/PostgreSQL** - Database
- **Redis** - Caching and queues (optional)
- **Livewire 3** - Real-time UI components (optional)

### ML Pipeline (Python)
- **Python 3.10+** - Core language
- **XGBoost 2.0.3** - Gradient boosting model
- **pandas 2.1.4** - Data manipulation
- **scikit-learn 1.3.2** - ML utilities
- **NumPy 1.26.2** - Numerical computing
- **TA-Lib 0.11.0** - Technical analysis

### Data Provider
- **Massive.com API** - Free tier (2 years EOD data)
- Alternatives: Alpha Vantage, Yahoo Finance, Polygon.io

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Laravel Application                   │
│                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Web Routes  │  │   Livewire   │  │    Artisan   │      │
│  │  (Optional)  │  │ Components   │  │   Commands   │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                  │                  │              │
│         └──────────────────┼──────────────────┘              │
│                            │                                 │
│                   ┌────────▼────────┐                        │
│                   │ Python Bridge   │                        │
│                   │    Service      │                        │
│                   └────────┬────────┘                        │
│                            │                                 │
│         ┌──────────────────┼──────────────────┐             │
│         │                  │                  │             │
│    ┌────▼─────┐    ┌──────▼──────┐    ┌─────▼─────┐       │
│    │  Stocks  │    │    Stock    │    │  Models   │       │
│    │   API    │    │   Prices    │    │  Config   │       │
│    └──────────┘    └─────────────┘    └───────────┘       │
└───────────────────────────┬─────────────────────────────────┘
                            │
                ┌───────────▼───────────┐
                │   Python ML Pipeline   │
                │                        │
                │  ┌──────────────────┐  │
                │  │     Feature      │  │
                │  │   Engineering    │  │
                │  └────────┬─────────┘  │
                │           │            │
                │  ┌────────▼─────────┐  │
                │  │  XGBoost Model   │  │
                │  │    Training      │  │
                │  └────────┬─────────┘  │
                │           │            │
                │  ┌────────▼─────────┐  │
                │  │   Backtesting    │  │
                │  │     Engine       │  │
                │  └────────┬─────────┘  │
                │           │            │
                └───────────┼────────────┘
                            │
                    ┌───────▼────────┐
                    │   Results &    │
                    │   Trade Data   │
                    └────────────────┘
```

---

## 📥 Installation

### Prerequisites

- PHP 8.2+
- Composer
- Python 3.10+
- MySQL or PostgreSQL
- Node.js & NPM (for frontend, optional)
- Git

### Step 1: Clone Repository

```bash
git clone https://github.com/Frankie813/smart-stock-trader.git
cd ml-trading-bot
```

### Step 2: Install Laravel Dependencies

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### Step 3: Configure Database

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=trading_bot
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
php artisan db:seed
```

### Step 4: Setup Python Environment

```bash
cd python
bash setup.sh
```

This creates a virtual environment and installs all Python dependencies.

### Step 5: Configure API Keys

Add to `.env`:

```env
MASSIVE_API_KEY=your_massive_api_key
PYTHON_PATH=python/venv/bin/python
PYTHON_SCRIPT_PATH=python
```

Get a free API key at [massive.com](https://massive.com)

### Step 6: Test Installation

```bash
# Test Python environment
cd python
python test_suite.py

# Test Laravel setup
php artisan test
```

If all tests pass, you're ready to go! ✅

---

## 🚀 Quick Start

### 1. Fetch Historical Data

```bash
# Fetch data for multiple stocks (takes ~5 minutes)
php artisan fetch:historical AAPL
php artisan fetch:historical MSFT
php artisan fetch:historical TSLA
php artisan fetch:historical NVDA
```

### 2. Train Your First Model

```bash
php artisan train:model AAPL
```

**Expected output:**
```
Training model for AAPL...
✓ Data exported
✓ Features engineered
✓ Model trained

Results:
Train Accuracy: 72.3%
Test Accuracy: 56.1%
Model saved: python/models/AAPL_model.pkl
```

### 3. Run Your First Experiment

```bash
php artisan experiment:run \
  --stocks=AAPL,MSFT,TSLA,NVDA \
  --capital=10000
```

**Expected output:**
```
Running experiment...

Training models...
[████████████████████] AAPL - 56.1%
[████████████████████] MSFT - 54.7%
[████████████████████] TSLA - 58.2%
[████████████████████] NVDA - 57.3%

Running backtests...
[████████████████████] AAPL - +12.5%
[████████████████████] MSFT - +8.3%
[████████████████████] TSLA - +18.7%
[████████████████████] NVDA - +15.2%

Overall Results:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total Return: +13.7%
Win Rate: 56.4%
Profit Factor: 1.82
Sharpe Ratio: 1.67
Max Drawdown: -18.3%
```

### 4. View Results

```bash
php artisan tinker

>>> Experiment::latest()->first()
>>> BacktestResult::where('experiment_id', 1)->get()
```

---

## ⚙️ Configuration

### Model Configurations

Configurations are stored in the `model_configurations` table. You can create custom configurations through the UI or database:

#### Conservative Strategy (Low Risk)
```php
[
    'hyperparameters' => [
        'n_estimators' => 100,
        'max_depth' => 4,
        'learning_rate' => 0.05,
    ],
    'trading_rules' => [
        'stop_loss_percent' => 1.5,
        'take_profit_percent' => 3.0,
        'confidence_threshold' => 0.70,  // Higher confidence required
        'max_daily_loss_percent' => 4.0,
    ],
]
```

#### Balanced Strategy (Recommended)
```php
[
    'hyperparameters' => [
        'n_estimators' => 150,
        'max_depth' => 5,
        'learning_rate' => 0.08,
    ],
    'trading_rules' => [
        'stop_loss_percent' => 2.0,
        'take_profit_percent' => 4.0,
        'confidence_threshold' => 0.65,
        'max_daily_loss_percent' => 6.0,
    ],
]
```

#### Aggressive Strategy (High Risk)
```php
[
    'hyperparameters' => [
        'n_estimators' => 200,
        'max_depth' => 6,
        'learning_rate' => 0.10,
    ],
    'trading_rules' => [
        'stop_loss_percent' => 3.0,
        'take_profit_percent' => 6.0,
        'confidence_threshold' => 0.60,  // Lower confidence acceptable
        'max_daily_loss_percent' => 8.0,
    ],
]
```

### Feature Selection

Enable/disable features in your configuration:

```php
'features_enabled' => [
    // Intraday (highly recommended)
    'gap_pct' => true,
    'open_to_close_pct' => true,
    'close_position' => true,
    
    // Technical indicators
    'rsi_14' => true,
    'macd' => true,
    'bb_width' => true,
    
    // Volume
    'volume_ratio' => true,
    
    // Multi-timeframe
    'return_5d' => true,
    
    // Time-based
    'is_monday' => true,
    'is_friday' => true,
]
```

---

## 📖 Usage Guide

### Command Reference

#### Training Commands

```bash
# Train model for single stock
php artisan train:model {symbol} [--config=name]

# Examples:
php artisan train:model AAPL
php artisan train:model TSLA --config="Aggressive Strategy"
```

#### Experiment Commands

```bash
# Run full experiment
php artisan experiment:run [options]

# Options:
--config=name          # Configuration name (default: "Default")
--stocks=AAPL,MSFT    # Comma-separated symbols
--start-date=YYYY-MM-DD  # Start of backtest period
--end-date=YYYY-MM-DD    # End of backtest period
--capital=10000       # Initial capital

# Examples:
php artisan experiment:run --stocks=AAPL,MSFT,TSLA

php artisan experiment:run \
  --config="Conservative Strategy" \
  --stocks=AAPL,MSFT,GOOGL,AMZN \
  --start-date=2023-01-01 \
  --capital=50000
```

#### Data Commands

```bash
# Fetch historical data
php artisan fetch:historical {symbol}

# Update existing data
php artisan fetch:update {symbol}

# Fetch multiple stocks
php artisan fetch:batch AAPL,MSFT,TSLA,NVDA
```

### Python Scripts (Direct Usage)

While the Artisan commands are recommended, you can also use Python scripts directly:

#### Train Model

```bash
cd python
source venv/bin/activate

python train_model.py AAPL '{
    "hyperparameters": {
        "n_estimators": 150,
        "max_depth": 5
    },
    "features_enabled": {
        "rsi_14": true,
        "macd": true
    }
}'
```

#### Run Backtest

```bash
python backtest.py AAPL '{
    "trading_rules": {
        "stop_loss_percent": 2.0,
        "take_profit_percent": 4.0,
        "confidence_threshold": 0.65
    }
}' 10000
```

---

## 📊 Understanding the Metrics

### Win Rate
**Definition:** Percentage of trades that were profitable.

**Formula:** `(Winning Trades / Total Trades) × 100`

**Example:** 38 wins out of 65 trades = 58.5% win rate

**Good:** >55% | **Excellent:** >60%

---

### Profit Factor
**Definition:** How much you make per dollar lost.

**Formula:** `Total Gross Profit / Total Gross Loss`

**Example:** $8,400 wins / $4,400 losses = 1.91 profit factor

**Good:** >1.5 | **Excellent:** >2.0

**Translation:** A profit factor of 1.91 means you earn $1.91 for every $1 you lose.

---

### Sharpe Ratio
**Definition:** Risk-adjusted returns (return per unit of risk).

**Formula:** `Average Return / Standard Deviation of Returns`

**Example:** 4% avg return / 2% volatility = 2.0 Sharpe ratio

**Good:** >1.0 | **Excellent:** >1.5

**Translation:** Higher Sharpe = more consistent returns with less volatility.

---

### Maximum Drawdown
**Definition:** Largest peak-to-valley decline in account balance.

**Formula:** `(Peak Value - Valley Value) / Peak Value × 100`

**Example:** Peak $12,000 → Valley $9,800 = -18.3% max drawdown

**Good:** <-20% | **Excellent:** <-15%

**Translation:** At your worst point, you were down 18.3% from your peak.

---

### Total Return
**Definition:** Overall percentage gain or loss.

**Formula:** `(Final Capital - Initial Capital) / Initial Capital × 100`

**Example:** Started $10,000 → Ended $11,370 = +13.7% return

**Good:** >20% annual | **Excellent:** >40% annual

---

### Prediction Accuracy
**Definition:** Percentage of predictions that were directionally correct.

**Formula:** `(Correct Predictions / Total Predictions) × 100`

**Example:** 56 correct out of 100 predictions = 56% accuracy

**Good:** >54% | **Excellent:** >58%

**Note:** Accuracy ≠ Profitability. Good risk management can make a 54% accurate model highly profitable.

---

## 🎯 Performance Expectations

### Realistic Targets (Daily Trading)

#### Good Performance ✓
```
Win Rate: 52-55%
Annual Return: +30-40%
Profit Factor: 1.3-1.6
Sharpe Ratio: 1.0-1.5
Max Drawdown: -20% to -25%

This is already better than 90% of day traders!
```

#### Excellent Performance 🏆
```
Win Rate: 56-60%
Annual Return: +50-70%
Profit Factor: 1.8-2.5
Sharpe Ratio: 1.5-2.5
Max Drawdown: -15% to -20%

This is institutional-grade performance.
```

#### Warning Signs ⚠️
```
Win Rate: <50%
Annual Return: Negative
Profit Factor: <1.0
Sharpe Ratio: <0.5
Max Drawdown: >-30%

Strategy needs significant improvement.
```

### Comparison to Benchmarks

| Strategy | Annual Return | Sharpe Ratio | Max Drawdown |
|----------|---------------|--------------|--------------|
| **S&P 500 (Buy & Hold)** | ~10% | 0.8-1.0 | -20% to -30% |
| **Professional Day Trader** | 20-50% | 1.0-2.0 | -15% to -25% |
| **Hedge Fund (Quant)** | 15-30% | 1.5-2.5 | -10% to -20% |
| **This Bot (Target)** | 30-50% | 1.5-2.0 | -15% to -20% |

### Important Notes

**⚠️ Past Performance ≠ Future Results**
- Backtests are optimistic (no slippage, perfect fills)
- Market conditions change
- Models degrade over time
- Real trading has additional costs

**✓ Start Small**
- Test with $1,000-$5,000 initially
- Scale up gradually as you prove profitability
- Never risk more than you can afford to lose

**✓ Monitor Performance**
- Track live vs backtest performance
- Retrain models monthly
- Adjust parameters as needed
- Stop trading if performance drops significantly

---

## 📁 Project Structure

```
ml-trading-bot/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── TrainModelCommand.php
│   │       ├── RunExperimentCommand.php
│   │       └── FetchHistoricalCommand.php
│   ├── Http/
│   │   └── Controllers/
│   ├── Livewire/
│   │   ├── Dashboard.php
│   │   ├── ConfigurationBuilder.php
│   │   └── ResultsViewer.php
│   ├── Models/
│   │   ├── Stock.php
│   │   ├── StockPrice.php
│   │   ├── ModelConfiguration.php
│   │   ├── Experiment.php
│   │   ├── BacktestResult.php
│   │   ├── BacktestTrade.php
│   │   └── Prediction.php
│   └── Services/
│       ├── MassiveApiService.php
│       └── PythonBridgeService.php
├── database/
│   ├── migrations/
│   │   ├── create_stocks_table.php
│   │   ├── create_stock_prices_table.php
│   │   ├── create_model_configurations_table.php
│   │   ├── create_experiments_table.php
│   │   ├── create_backtest_results_table.php
│   │   ├── create_backtest_trades_table.php
│   │   └── create_predictions_table.php
│   └── seeders/
│       ├── StocksSeeder.php
│       └── ModelConfigurationsSeeder.php
├── python/
│   ├── venv/                    # Virtual environment (created by setup)
│   ├── models/                  # Trained models (.pkl files)
│   ├── data/                    # Training data (CSV files)
│   ├── logs/                    # Training/backtest logs
│   ├── feature_engineering.py  # Feature calculation
│   ├── train_model.py          # Model training script
│   ├── backtest.py             # Backtesting engine
│   ├── utils.py                # Helper functions
│   ├── requirements.txt        # Python dependencies
│   ├── setup.sh                # Setup script
│   ├── test_suite.py           # Test all components
│   └── README.md               # Python documentation
├── resources/
│   └── views/
│       └── livewire/
│           ├── dashboard.blade.php
│           └── ...
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
│   ├── app/
│   │   └── training_data/      # Exported CSV data
│   └── logs/
│       ├── laravel.log
│       └── python.log
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── composer.json
├── package.json
├── phpunit.xml
├── README.md                   # This file
└── LICENSE
```

---

## 🔌 API Reference

### PythonBridgeService

Main service for interacting with Python ML pipeline.

#### `exportStockData(Stock $stock, string $startDate, string $endDate): string`

Exports stock data to CSV for training.

```php
$service = app(PythonBridgeService::class);
$csvPath = $service->exportStockData($stock, '2023-01-01', '2024-12-31');
```

#### `trainModel(Stock $stock, ModelConfiguration $config): array`

Trains XGBoost model for a stock.

```php
$results = $service->trainModel($stock, $config);
// Returns: ['train_accuracy' => 72.3, 'test_accuracy' => 56.1, ...]
```

#### `runBacktest(Stock $stock, ModelConfiguration $config, float $capital): array`

Runs backtest using trained model.

```php
$results = $service->runBacktest($stock, $config, 10000);
// Returns: ['win_rate' => 58.4, 'total_return' => 12.5, ...]
```

### MassiveApiService

Service for fetching stock data.

#### `fetchHistoricalData(Stock $stock, int $years = 2): bool`

Fetches historical OHLCV data.

```php
$service = app(MassiveApiService::class);
$success = $service->fetchHistoricalData($stock, 2);
```

---

## 🐛 Troubleshooting

### Common Issues

#### Python Environment Not Found

**Error:** `python: command not found`

**Solution:**
```bash
cd python
bash setup.sh
source venv/bin/activate
```

#### Missing Python Dependencies

**Error:** `ModuleNotFoundError: No module named 'xgboost'`

**Solution:**
```bash
cd python
source venv/bin/activate
pip install -r requirements.txt --break-system-packages
```

#### No Historical Data

**Error:** `No data found for stock AAPL`

**Solution:**
```bash
php artisan fetch:historical AAPL
# Wait for completion, then retry
```

#### Model Training Fails

**Error:** `Train accuracy: 100%, Test accuracy: 50%` (overfitting)

**Solution:**
Adjust hyperparameters in configuration:
```php
'max_depth' => 3,           // Reduce from 5
'min_child_weight' => 5,    // Increase from 2
'gamma' => 1.0,             // Increase from 0.3
```

#### Low Prediction Accuracy (<52%)

**Possible causes:**
1. Insufficient training data (need 500+ days)
2. Stock too volatile (try more stable stocks)
3. Feature engineering not capturing patterns
4. Random market movements (cannot be predicted)

**Solutions:**
```bash
# Fetch more data
php artisan fetch:historical AAPL

# Try different stock
php artisan train:model MSFT

# Adjust features in configuration
```

#### Laravel Permission Errors

**Error:** `Permission denied when writing to storage/`

**Solution:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Debug Mode

Enable detailed logging:

```env
# .env
APP_DEBUG=true
LOG_LEVEL=debug
```

View logs:
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/python.log
tail -f python/logs/train_*.log
```

### Getting Help

1. **Check documentation:** Review all README files
2. **Run test suite:** `python test_suite.py`
3. **Check logs:** Review error messages
4. **GitHub Issues:** Open an issue with full error output
5. **Discord/Slack:** Join our community (link in profile)

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### Ways to Contribute

- 🐛 **Report Bugs** - Open an issue with reproduction steps
- 💡 **Suggest Features** - Propose new features or improvements
- 📝 **Improve Documentation** - Fix typos, add examples
- 🔧 **Submit PRs** - Fix bugs or implement features
- 🧪 **Write Tests** - Improve test coverage
- 🎨 **Design UI** - Improve dashboard and visualizations

### Development Setup

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Run tests: `php artisan test && python test_suite.py`
5. Commit changes: `git commit -m 'Add amazing feature'`
6. Push to branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Coding Standards

- **PHP:** Follow PSR-12 coding standard
- **Python:** Follow PEP 8 style guide
- **Tests:** Write tests for new features
- **Documentation:** Update README for significant changes

### Pull Request Process

1. Ensure all tests pass
2. Update documentation
3. Add entry to CHANGELOG.md
4. Request review from maintainers
5. Address review feedback
6. Squash commits before merge

---

## 🗺 Roadmap

### ✅ Phase 1 - POC (Current)
- [x] Database layer
- [x] API integration
- [x] ML pipeline (XGBoost)
- [x] Backtesting engine
- [x] Risk management
- [x] Documentation

### 🔄 Phase 2 - Enhancement (Q2 2025)
- [ ] Livewire dashboard UI
- [ ] Multiple ML models (LSTM, Random Forest)
- [ ] Advanced feature engineering
- [ ] Hyperparameter optimization (grid search)
- [ ] Portfolio diversification
- [ ] PDF report generation

### 🔮 Phase 3 - Production (Q3 2025)
- [ ] Paper trading integration
- [ ] Real-time data feeds (Polygon.io)
- [ ] 30-minute predictions (intraday)
- [ ] Live trading execution (Alpaca API)
- [ ] Email/SMS alerts
- [ ] Mobile app (optional)

### 🚀 Phase 4 - Scale (Q4 2025)
- [ ] Multi-user support
- [ ] Subscription model
- [ ] API for third-party integration
- [ ] Cloud deployment (AWS/GCP)
- [ ] Advanced analytics
- [ ] AI-powered strategy optimization

**Want to contribute to any of these?** Open an issue or PR!

---

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### MIT License Summary

```
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
```

---

## ⚖️ Legal Disclaimer

**IMPORTANT - PLEASE READ CAREFULLY**

This software is provided for **educational and research purposes only**.

### Trading Risks

- **Past performance does not guarantee future results**
- **Trading stocks involves substantial risk of loss**
- **You can lose some or all of your investment**
- **Only invest money you can afford to lose**
- **Not financial advice - consult a licensed advisor**

### No Warranties

- Software provided "AS IS" without warranties
- No guarantee of profitability or accuracy
- Authors not liable for financial losses
- Use entirely at your own risk

### Regulatory Compliance

- **Know your local laws** regarding algorithmic trading
- **Securities regulations** vary by jurisdiction
- **Tax implications** - consult a tax professional
- **Pattern day trading rules** may apply (US: 25k minimum)

### Data Usage

- Respect API rate limits
- Review data provider terms of service
- Don't use for high-frequency trading without permission
- Ensure compliance with data licensing

**By using this software, you acknowledge and accept all risks.**

---

## 🙏 Acknowledgments

### Built With

- [Laravel](https://laravel.com) - PHP framework
- [XGBoost](https://xgboost.ai) - Gradient boosting library
- [Livewire](https://laravel-livewire.com) - Dynamic UI framework
- [Flux Pro](https://fluxui.dev) - UI component library
- [TA-Lib](https://ta-lib.org) - Technical analysis library

### Data Providers

- [Massive.com](https://massive.com) - Historical stock data
- [Alpha Vantage](https://www.alphavantage.co) - Alternative data source
- [Yahoo Finance](https://finance.yahoo.com) - Market data

### Inspiration

- quantopian - Algorithmic trading platform (defunct but inspirational)
- QuantConnect - Open-source trading platform
- backtrader - Python backtesting library
- Machine Learning for Algorithmic Trading (Stefan Jansen)

### Contributors

Special thanks to all contributors who have helped improve this project!

[See all contributors →](https://github.com/Frankie813/smart-stock-trader/graphs/contributors)

---

## 📞 Contact & Support

### Community

- **GitHub Issues:** [Report bugs or request features](https://github.com/Frankie813/smart-stock-trader/issues)
- **Discussions:** [Ask questions and share ideas](https://github.com/Frankie813/smart-stock-trader/discussions)

### Project Creator

- **GitHub:** [@Frankie813](https://github.com/Frankie813)
- **Email:** frankie@endlesshorizon.studio
- **Website:** https://frankietm.dev

---

## 🌟 Star History

If you find this project useful, please consider giving it a star! ⭐

[![Star History Chart](https://api.star-history.com/svg?repos=Frankie813/smart-stock-trader&type=Date)](https://star-history.com/#Frankie813/smart-stock-trader&Date)

---

## 📈 Project Stats

![GitHub stars](https://img.shields.io/github/stars/Frankie813/smart-stock-trader?style=social)
![GitHub forks](https://img.shields.io/github/forks/Frankie813/smart-stock-trader?style=social)
![GitHub issues](https://img.shields.io/github/issues/Frankie813/smart-stock-trader)
![GitHub pull requests](https://img.shields.io/github/issues-pr/Frankie813/smart-stock-trader)
![GitHub last commit](https://img.shields.io/github/last-commit/Frankie813/smart-stock-trader)
![GitHub code size](https://img.shields.io/github/languages/code-size/Frankie813/smart-stock-trader)

---

<div align="center">

**Made with ❤️ by developers who believe in open-source ML trading**

[⬆ Back to Top](#-ml-stock-trading-bot---proof-of-concept)

</div>
