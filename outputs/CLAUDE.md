# Stock Trading Bot - Proof of Concept

## Project Overview

This is a personal proof-of-concept application that uses machine learning to predict stock price movements (up/down) based on historical data. The goal is to backtest predictions against actual outcomes to validate profitability before considering live trading.

**Key Objective:** Train and validate an XGBoost model on 2 years of historical stock data, measure prediction accuracy and potential profitability, and demonstrate viability to interested parties.

## Goals

- Train an XGBoost model on 2 years of historical end-of-day stock data
- Backtest predictions against actual market outcomes
- Measure profitability metrics (profit/loss, win rate, accuracy, Sharpe ratio)
- Create a dashboard to visualize performance
- Validate the concept before investing in real-time trading infrastructure

## Technical Stack

### Frontend
- **Laravel 11**: Main application framework
- **Livewire 3**: For reactive components
- **Flux Pro**: Premium UI component library (use extensively)
- **Tailwind CSS**: Utility-first styling

### Backend
- **Laravel 11**: API endpoints, scheduling, database management
- **MySQL/PostgreSQL**: Store historical data, predictions, backtest results

### Machine Learning
- **Python 3.10+**: Data processing and ML training
- **XGBoost**: Primary algorithm for price prediction
- **Pandas**: Data manipulation
- **NumPy**: Numerical operations
- **scikit-learn**: Data preprocessing and metrics
- **TA-Lib**: Technical analysis indicators

### Data Source
- **massive.com API** (Free Tier):
  - 5 API calls per minute
  - 2 years historical end-of-day data
  - 100% US market coverage
  - Technical indicators
  - Minute aggregates (for future use)

## Target Stocks (Initial POC)

Start with high-liquidity stocks:
- Apple (AAPL)
- Microsoft (MSFT)
- Tesla (TSLA)
- Nvidia (NVDA)
- Additional stocks can be added as needed

## Architecture

```
┌─────────────────────────────────────────┐
│         Laravel Application             │
│  ┌───────────────────────────────────┐  │
│  │    Livewire + Flux Pro UI         │  │
│  │  - Dashboard                      │  │
│  │  - Stock management               │  │
│  │  - Backtest results viewer        │  │
│  │  - Performance charts             │  │
│  └───────────────────────────────────┘  │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │    Artisan Commands               │  │
│  │  - FetchHistoricalData            │  │
│  │  - TrainModels                    │  │
│  │  - RunBacktest                    │  │
│  │  - CalculateMetrics               │  │
│  └───────────────────────────────────┘  │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │    Services                       │  │
│  │  - MassiveApiService              │  │
│  │  - PythonBridgeService            │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
                   │
                   ▼ (shell_exec)
┌─────────────────────────────────────────┐
│         Python Scripts                  │
│  python/                                │
│  ├── venv/                              │
│  ├── models/ (saved .pkl files)         │
│  ├── data_fetcher.py                    │
│  ├── feature_engineering.py             │
│  ├── train_model.py                     │
│  ├── backtest.py                        │
│  ├── metrics.py                         │
│  └── requirements.txt                   │
└─────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│         Database (MySQL/PostgreSQL)     │
│  - stocks                               │
│  - stock_prices                         │
│  - predictions                          │
│  - backtest_results                     │
│  - backtest_trades                      │
└─────────────────────────────────────────┘
```

## Database Schema

### stocks
- id
- symbol (e.g., "AAPL")
- name (e.g., "Apple Inc.")
- is_active (boolean)
- created_at, updated_at

### stock_prices
- id
- stock_id (foreign key)
- date
- open, high, low, close (decimal)
- volume (bigint)
- adjusted_close (decimal, nullable)
- created_at, updated_at
- UNIQUE KEY (stock_id, date)

### predictions
- id
- stock_id (foreign key)
- prediction_date
- predicted_direction (enum: 'up', 'down')
- confidence_score (decimal 0-1)
- model_version (string)
- features_used (json, nullable)
- created_at, updated_at

### backtest_results
- id
- stock_id (foreign key, nullable for overall results)
- start_date, end_date
- total_trades (integer)
- winning_trades (integer)
- losing_trades (integer)
- win_rate (decimal)
- total_profit_loss (decimal)
- accuracy_percentage (decimal)
- sharpe_ratio (decimal, nullable)
- max_drawdown (decimal, nullable)
- model_version (string)
- created_at, updated_at

### backtest_trades
- id
- backtest_result_id (foreign key)
- stock_id (foreign key)
- trade_date
- prediction (enum: 'up', 'down')
- actual_direction (enum: 'up', 'down')
- was_correct (boolean)
- entry_price (decimal)
- exit_price (decimal)
- profit_loss (decimal)
- created_at, updated_at

## Features to Engineer (Technical Indicators)

The Python scripts should calculate the following technical indicators:

### Trend Indicators
- **SMA (Simple Moving Average)**: 10, 20, 50, 200 day
- **EMA (Exponential Moving Average)**: 12, 26 day
- **MACD**: Moving Average Convergence Divergence
- **ADX**: Average Directional Index

### Momentum Indicators
- **RSI**: Relative Strength Index (14 day)
- **Stochastic Oscillator**: %K and %D
- **ROC**: Rate of Change
- **Momentum**: Price momentum

### Volatility Indicators
- **Bollinger Bands**: Upper, middle, lower bands
- **ATR**: Average True Range
- **Standard Deviation**: 20 day

### Volume Indicators
- **Volume Change Rate**
- **Volume Moving Average**
- **OBV**: On-Balance Volume
- **Volume Price Trend**

### Price-Based Features
- **Daily Return**: (close - previous_close) / previous_close
- **High-Low Range**: (high - low) / close
- **Gap**: (open - previous_close) / previous_close

## Development Phases

### Phase 1: Data Infrastructure ✓
1. Set up Laravel project with Livewire and Flux Pro
2. Create database migrations and models
3. Build MassiveApiService for data fetching
4. Create Artisan command to fetch and store 2 years of historical data
5. Verify data integrity

### Phase 2: Feature Engineering
1. Set up Python virtual environment
2. Create feature_engineering.py script
3. Calculate all technical indicators
4. Store engineered features in database or CSV files
5. Validate feature calculations

### Phase 3: Model Training
1. Create train_model.py script
2. Implement train/test split (e.g., 80/20)
3. Train XGBoost classifier
4. Save trained model (.pkl file)
5. Create Laravel command to trigger Python training

### Phase 4: Backtesting Engine
1. Create backtest.py script
2. Simulate trading based on model predictions
3. Calculate profit/loss for each trade
4. Store results in backtest_results and backtest_trades tables
5. Create Laravel command to trigger backtesting

### Phase 5: Dashboard & Visualization
1. Create Livewire Dashboard component
2. Build StockList component (manage tracked stocks)
3. Build BacktestResults component (display performance)
4. Build PredictionTable component (show predictions vs actuals)
5. Add performance charts using Flux Pro components
6. Implement real-time progress updates via Livewire events

## Key Metrics to Track

Calculate and display the following:

- **Prediction Accuracy**: % of correct predictions
- **Total Profit/Loss**: Cumulative P&L in dollars
- **Win Rate**: % of profitable trades
- **Sharpe Ratio**: Risk-adjusted returns
- **Maximum Drawdown**: Largest peak-to-trough decline
- **Average Profit per Trade**
- **Average Loss per Trade**
- **Profit Factor**: Gross profit / gross loss
- **Number of Trades**: Total trades executed in backtest

## Python Script Requirements

### train_model.py
```python
# Usage: python python/train_model.py AAPL
# Outputs: Trained model saved to python/models/AAPL_model.pkl
```

**Responsibilities:**
1. Load historical price data and features
2. Split data (80% train, 20% test)
3. Train XGBoost classifier (predict up/down next day)
4. Evaluate on test set
5. Save model with metadata (accuracy, date trained, version)
6. Return training results as JSON

### backtest.py
```python
# Usage: python python/backtest.py AAPL
# Outputs: Backtest results as JSON
```

**Responsibilities:**
1. Load trained model
2. Load test data (unseen by model)
3. Generate predictions for each day
4. Simulate trades (buy at open if "up", sell at close)
5. Calculate P&L for each trade
6. Aggregate metrics
7. Return results as JSON for Laravel to store

### feature_engineering.py
```python
# Usage: python python/feature_engineering.py AAPL
# Outputs: CSV with features appended
```

**Responsibilities:**
1. Load raw price data
2. Calculate all technical indicators
3. Handle missing values
4. Create target variable (1 if next day up, 0 if down)
5. Save engineered dataset

## Laravel + Python Integration

### Calling Python from Laravel

```php
// In app/Services/PythonBridgeService.php
$pythonPath = base_path('python/venv/bin/python');
$scriptPath = base_path('python/train_model.py');
$output = shell_exec("$pythonPath $scriptPath AAPL 2>&1");
$result = json_decode($output, true);
```

### Environment Variables (.env)
```
PYTHON_PATH=/path/to/python/venv/bin/python
MASSIVE_API_KEY=your_api_key_here
MASSIVE_API_BASE_URL=https://api.massive.com/v1
```

## Livewire Components Structure

```
app/Livewire/
├── Dashboard.php              # Main overview with key metrics
├── StockList.php              # Add/remove stocks, trigger training
├── TrainModel.php             # Button to train models, show progress
├── BacktestResults.php        # Display backtest performance by stock
├── PredictionTable.php        # Show predictions vs actual outcomes
└── PerformanceChart.php       # Visualize profit/loss over time
```

## Flux Pro Component Usage

Use Flux Pro components extensively for professional UI:

- **flux:card** - For metric displays and content sections
- **flux:table** - For data tables (predictions, trades)
- **flux:button** - For actions (train, backtest, refresh)
- **flux:badge** - For status indicators (win/loss, up/down)
- **flux:heading** - For section headers
- **flux:modal** - For detailed views
- **flux:select** - For stock selection dropdowns
- **flux:chart** - For performance visualizations

## Important Constraints & Notes

### Current Limitations
- **Backtesting Only**: No live trading functionality
- **End-of-Day Data**: Free tier provides daily close data only
- **Rate Limits**: 5 API calls per minute from massive.com
- **Historical Range**: 2 years maximum on free tier
- **Single Model**: Starting with XGBoost only

### Future Enhancements (Out of Scope for POC)
- Real-time trading execution
- Intraday 5-10 minute predictions (requires paid tier)
- Multiple ML model comparison (Random Forest, LSTM)
- Advanced portfolio management
- Comprehensive risk management systems
- Live data streaming

### API Rate Limiting
When fetching data:
- Respect 5 calls/minute limit
- Implement delays between requests
- Cache data to avoid redundant calls
- Handle rate limit errors gracefully

## Development Workflow

1. **Fetch Data**: Run `php artisan fetch:historical-data AAPL`
2. **Engineer Features**: Triggered automatically or manually
3. **Train Model**: Run `php artisan train:model AAPL`
4. **Run Backtest**: Run `php artisan run:backtest AAPL`
5. **View Results**: Check dashboard in browser

## Code Quality Standards

- **Laravel**: Follow PSR-12 coding standards
- **Python**: Follow PEP 8 style guide
- **Comments**: Explain complex logic, especially ML decisions
- **Error Handling**: Comprehensive try-catch blocks
- **Logging**: Log all API calls, training runs, and errors
- **Validation**: Validate all user inputs and API responses

## Testing Strategy

### Unit Tests (Laravel)
- Test API service methods
- Test model relationships
- Test calculation logic

### Integration Tests
- Test full workflow: fetch → train → backtest
- Test Python script execution from Laravel
- Test data persistence

### Manual Testing
- Verify predictions make sense
- Check backtest logic with known scenarios
- Validate metrics calculations

## Deployment Notes

This POC is designed for local development initially. For deployment to Laravel Forge:

1. Ensure Python 3.10+ is installed on server
2. Set up virtual environment in deployment script
3. Install Python dependencies from requirements.txt
4. Configure PYTHON_PATH environment variable
5. Ensure storage directories are writable
6. Set up Laravel scheduler for automated tasks

## Questions to Resolve

- [ ] Exact massive.com API endpoints and authentication method
- [ ] Preferred database (MySQL vs PostgreSQL)
- [ ] Initial trading simulation capital amount
- [ ] Commission/fee structure to simulate in backtesting
- [ ] How to handle stock splits and dividends

## Getting Started Checklist

- [ ] Install Laravel and set up project
- [ ] Install Livewire and Flux Pro
- [ ] Create database and run migrations
- [ ] Set up Python virtual environment
- [ ] Install Python dependencies
- [ ] Get massive.com API key
- [ ] Fetch first stock data (AAPL)
- [ ] Train first model
- [ ] Run first backtest
- [ ] Build dashboard to view results

---

**Project Start Date**: [Current Date]  
**Target Completion**: [Your Timeline]  
**Developer**: Personal Project  
**Status**: Planning → Development
