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

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.15
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v3
- livewire/volt (VOLT) - v1
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== fluxui-free/core rules ===

## Flux UI Free

- This project is using the free edition of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted, UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use Laravel Boost's `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:

<code-snippet name="Flux UI Component Usage Example" lang="blade">
    <flux:button variant="primary"/>
</code-snippet>


### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, profile, radio, select, separator, switch, text, textarea, tooltip
</available-flux-components>


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== volt/core rules ===

## Livewire Volt

- This project uses Livewire Volt for interactivity within its pages. New pages requiring interactivity must also use Livewire Volt. There is documentation available for it.
- Make new Volt components using `php artisan make:volt [name] [--test] [--pest]`
- Volt is a **class-based** and **functional** API for Livewire that supports single-file components, allowing a component's PHP logic and Blade templates to co-exist in the same file
- Livewire Volt allows PHP logic and Blade templates in one file. Components use the `@volt` directive.
- You must check existing Volt components to determine if they're functional or class based. If you can't detect that, ask the user which they prefer before writing a Volt component.

### Volt Functional Component Example

<code-snippet name="Volt Functional Component Example" lang="php">
@volt
<?php
use function Livewire\Volt\{state, computed};

state(['count' => 0]);

$increment = fn () => $this->count++;
$decrement = fn () => $this->count--;

$double = computed(fn () => $this->count * 2);
?>

<div>
    <h1>Count: {{ $count }}</h1>
    <h2>Double: {{ $this->double }}</h2>
    <button wire:click="increment">+</button>
    <button wire:click="decrement">-</button>
</div>
@endvolt
</code-snippet>


### Volt Class Based Component Example
To get started, define an anonymous class that extends Livewire\Volt\Component. Within the class, you may utilize all of the features of Livewire using traditional Livewire syntax:


<code-snippet name="Volt Class-based Volt Component Example" lang="php">
use Livewire\Volt\Component;

new class extends Component {
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }
} ?>

<div>
    <h1>{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
</code-snippet>


### Testing Volt & Volt Components
- Use the existing directory for tests if it already exists. Otherwise, fallback to `tests/Feature/Volt`.

<code-snippet name="Livewire Test Example" lang="php">
use Livewire\Volt\Volt;

test('counter increments', function () {
    Volt::test('counter')
        ->assertSee('Count: 0')
        ->call('increment')
        ->assertSee('Count: 1');
});
</code-snippet>


<code-snippet name="Volt Component Test Using Pest" lang="php">
declare(strict_types=1);

use App\Models\{User, Product};
use Livewire\Volt\Volt;

test('product form creates product', function () {
    $user = User::factory()->create();

    Volt::test('pages.products.create')
        ->actingAs($user)
        ->set('form.name', 'Test Product')
        ->set('form.description', 'Test Description')
        ->set('form.price', 99.99)
        ->call('create')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Test Product')->exists())->toBeTrue();
});
</code-snippet>


### Common Patterns


<code-snippet name="CRUD With Volt" lang="php">
<?php

use App\Models\Product;
use function Livewire\Volt\{state, computed};

state(['editing' => null, 'search' => '']);

$products = computed(fn() => Product::when($this->search,
    fn($q) => $q->where('name', 'like', "%{$this->search}%")
)->get());

$edit = fn(Product $product) => $this->editing = $product->id;
$delete = fn(Product $product) => $product->delete();

?>

<!-- HTML / UI Here -->
</code-snippet>

<code-snippet name="Real-Time Search With Volt" lang="php">
    <flux:input
        wire:model.live.debounce.300ms="search"
        placeholder="Search..."
    />
</code-snippet>

<code-snippet name="Loading States With Volt" lang="php">
    <flux:button wire:click="save" wire:loading.attr="disabled">
        <span wire:loading.remove>Save</span>
        <span wire:loading>Saving...</span>
    </flux:button>
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
