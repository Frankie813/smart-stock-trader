# Database Layer - Stock Trading Bot

This folder contains all migrations, models, factories, and seeders for the Stock Trading Bot POC.

## Installation Steps

### 1. Copy Files to Your Laravel Project

```bash
# Copy migrations
cp migrations/* /path/to/your/laravel/database/migrations/

# Copy models
cp models/* /path/to/your/laravel/app/Models/

# Copy factories
cp factories/* /path/to/your/laravel/database/factories/

# Copy seeders
cp seeders/* /path/to/your/laravel/database/seeders/
```

### 2. Rename Migration Files

Laravel requires migrations to have timestamps. Rename them like this:

```bash
cd /path/to/your/laravel/database/migrations/

# Rename format: YYYY_MM_DD_HHMMSS_migration_name.php
mv 001_create_stocks_table.php 2025_01_15_000001_create_stocks_table.php
mv 002_create_stock_prices_table.php 2025_01_15_000002_create_stock_prices_table.php
mv 003_create_model_configurations_table.php 2025_01_15_000003_create_model_configurations_table.php
mv 004_create_experiments_table.php 2025_01_15_000004_create_experiments_table.php
mv 005_create_predictions_table.php 2025_01_15_000005_create_predictions_table.php
mv 006_create_backtest_results_table.php 2025_01_15_000006_create_backtest_results_table.php
mv 007_create_backtest_trades_table.php 2025_01_15_000007_create_backtest_trades_table.php
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Seed Initial Data

```bash
php artisan db:seed
```

This will populate:
- 8 popular stocks (AAPL, MSFT, TSLA, NVDA, etc.)
- 3 preset model configurations (Conservative, Aggressive, Balanced)

---

## Database Structure

### Core Tables

#### `stocks`
Stores stock symbols and metadata
- `symbol`: Stock ticker (e.g., "AAPL")
- `name`: Company name
- `exchange`: Trading exchange (NASDAQ, NYSE)
- `is_active`: Whether to track this stock

#### `stock_prices`
Historical OHLCV (Open, High, Low, Close, Volume) data
- One record per stock per day
- Stores raw market data from API

#### `model_configurations`
Stores ML model settings and strategies
- `hyperparameters`: XGBoost settings (JSON)
- `features_enabled`: Which technical indicators to use (JSON)
- `trading_rules`: Stop loss, take profit, confidence threshold (JSON)
- `is_default`: Mark default configuration

#### `experiments`
Tracks backtest runs
- Links to a configuration
- Stores date range and initial capital
- Tracks progress (0-100%)
- Stores aggregated results (JSON)

#### `predictions`
Individual model predictions
- Links to stock and experiment
- Stores predicted direction (up/down) and confidence
- Updates with actual outcome for accuracy tracking

#### `backtest_results`
Aggregated performance metrics
- Can be stock-specific or overall (null stock_id)
- Stores all key metrics (win rate, Sharpe ratio, drawdown, etc.)

#### `backtest_trades`
Individual trades from backtesting
- Entry/exit dates and prices
- Profit/loss for each trade
- Why trade exited (eod, stop_loss, take_profit)

---

## Model Relationships

```
Stock
├── hasMany StockPrice
├── hasMany Prediction
├── hasMany BacktestResult
└── hasMany BacktestTrade

ModelConfiguration
├── hasMany Experiment
└── hasMany BacktestResult

Experiment
├── belongsTo ModelConfiguration
├── hasMany BacktestResult
└── hasMany Prediction

BacktestResult
├── belongsTo Experiment
├── belongsTo Stock (nullable)
├── belongsTo ModelConfiguration
└── hasMany BacktestTrade

BacktestTrade
├── belongsTo BacktestResult
└── belongsTo Stock

Prediction
├── belongsTo Stock
└── belongsTo Experiment
```

---

## Model Features

### Stock Model

```php
// Get latest price
$stock->latestPrice();

// Get prices in date range
$stock->pricesInRange('2024-01-01', '2024-12-31');

// Get recent prediction accuracy
$stock->getRecentAccuracy(30); // Last 30 days
```

### StockPrice Model

```php
// Check if price went up from previous day
$price->wentUp(); // boolean

// Get daily return percentage
$price->daily_return; // accessor

// Get intraday range
$price->intraday_range; // accessor
```

### ModelConfiguration Model

```php
// Get count of enabled features
$config->enabled_features_count; // accessor

// Get list of enabled features
$config->enabled_features_list; // accessor

// Get average performance
$config->getAveragePerformance();

// Set as default
$config->setAsDefault();

// Query default configuration
ModelConfiguration::default()->first();
```

### Experiment Model

```php
// Check status
$experiment->isCompleted(); // boolean
$experiment->isRunning(); // boolean
$experiment->hasFailed(); // boolean

// Get duration
$experiment->duration; // accessor (seconds)

// Get key metrics
$experiment->total_return; // accessor
$experiment->win_rate; // accessor
$experiment->sharpe_ratio; // accessor

// Mark as started
$experiment->markAsStarted();

// Mark as completed
$experiment->markAsCompleted($results);

// Mark as failed
$experiment->markAsFailed($errorMessage);

// Update progress
$experiment->updateProgress(50); // 0-100

// Query scopes
Experiment::completed()->get();
Experiment::running()->get();
```

### Prediction Model

```php
// Check prediction type
$prediction->isBullish(); // boolean
$prediction->isBearish(); // boolean

// Check if actual outcome known
$prediction->hasActualOutcome(); // boolean

// Get confidence percentage
$prediction->confidence_percentage; // accessor

// Update with actual outcome
$prediction->updateActualOutcome('up');

// Query scopes
Prediction::correct()->get();
Prediction::incorrect()->get();
Prediction::withActual()->get();
Prediction::highConfidence(0.65)->get();
```

### BacktestResult Model

```php
// Check if overall result
$result->isOverall(); // boolean

// Check if profitable
$result->isProfitable(); // boolean

// Get profit/loss amount
$result->profit_loss_amount; // accessor

// Get annualized return
$result->annualized_return; // accessor

// Get risk-adjusted score
$result->risk_adjusted_score; // accessor

// Query scopes
BacktestResult::profitable()->get();
BacktestResult::unprofitable()->get();
BacktestResult::overall()->get();
BacktestResult::forStock()->get();
```

### BacktestTrade Model

```php
// Check if profitable
$trade->isProfitable(); // boolean
$trade->isLoss(); // boolean

// Get holding period
$trade->holding_period; // accessor (days)

// Get investment amounts
$trade->total_invested; // accessor
$trade->total_return; // accessor
$trade->net_profit_loss; // accessor (after commission)

// Check prediction type
$trade->wasBullish(); // boolean
$trade->wasBearish(); // boolean

// Get exit reason text
$trade->exit_reason_text; // accessor

// Query scopes
BacktestTrade::winning()->get();
BacktestTrade::losing()->get();
BacktestTrade::correct()->get();
BacktestTrade::incorrect()->get();
BacktestTrade::stopLoss()->get();
BacktestTrade::takeProfit()->get();
```

---

## Example Queries

### Get all profitable experiments

```php
$profitable = Experiment::completed()
    ->get()
    ->filter(fn($exp) => $exp->total_return > 0);
```

### Get best performing configuration

```php
$best = ModelConfiguration::all()
    ->map(fn($config) => [
        'config' => $config,
        'avg_return' => $config->experiments()
            ->completed()
            ->avg('results->overall->total_return')
    ])
    ->sortByDesc('avg_return')
    ->first();
```

### Get AAPL prediction accuracy

```php
$aapl = Stock::where('symbol', 'AAPL')->first();
$accuracy = $aapl->predictions()
    ->withActual()
    ->avg('was_correct') * 100;
```

### Get recent winning trades

```php
$winningTrades = BacktestTrade::winning()
    ->with('stock')
    ->latest('exit_date')
    ->take(10)
    ->get();
```

### Compare two experiments

```php
$exp1 = Experiment::find(1);
$exp2 = Experiment::find(2);

$comparison = [
    'exp1' => [
        'return' => $exp1->total_return,
        'win_rate' => $exp1->win_rate,
        'sharpe' => $exp1->sharpe_ratio,
    ],
    'exp2' => [
        'return' => $exp2->total_return,
        'win_rate' => $exp2->win_rate,
        'sharpe' => $exp2->sharpe_ratio,
    ],
];
```

---

## Preset Configurations

After seeding, you'll have 3 preset strategies:

### Conservative Strategy (Default)
- Lower max_depth (3)
- Lower learning_rate (0.05)
- Fewer features (only proven indicators)
- Strict stop loss (2%)
- Lower take profit (3%)
- High confidence threshold (65%)

### Aggressive Strategy
- Higher max_depth (7)
- Higher learning_rate (0.1)
- All features enabled
- Wider stop loss (5%)
- Higher take profit (10%)
- Lower confidence threshold (50%)

### Balanced Strategy
- Medium settings
- Most useful features
- Moderate risk parameters
- 58% confidence threshold

---

## Next Steps

1. ✅ Migrations and models created
2. 🔜 Create Artisan commands to fetch stock data
3. 🔜 Create API service for massive.com
4. 🔜 Build Python scripts for ML training
5. 🔜 Create Livewire components for UI
6. 🔜 Add authentication (if needed)

---

## Testing

Run tests to ensure everything works:

```bash
# Create a test
php artisan make:test StockModelTest

# Run tests
php artisan test
```

Example test:

```php
use App\Models\Stock;

test('stock can calculate recent accuracy', function () {
    $stock = Stock::factory()->create();
    
    // Create some predictions
    Prediction::factory()
        ->for($stock)
        ->correct()
        ->count(7)
        ->create();
        
    Prediction::factory()
        ->for($stock)
        ->incorrect()
        ->count(3)
        ->create();
    
    expect($stock->getRecentAccuracy(30))->toBe(70.0);
});
```

---

## Questions?

If you need help:
1. Check model relationships in each model file
2. Look at accessor methods (get...Attribute)
3. Review query scopes (scope...)
4. Check the seeders for example data structure

Ready to build the next layer! 🚀
