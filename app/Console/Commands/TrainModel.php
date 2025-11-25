<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\PythonBridgeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class TrainModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'train:model
                            {symbol : The stock symbol (e.g., AAPL)}
                            {--start-date= : Start date for training data (YYYY-MM-DD). Defaults to 2 years ago}
                            {--end-date= : End date for training data (YYYY-MM-DD). Defaults to today}
                            {--force : Retrain even if model already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Train XGBoost model for stock price prediction';

    protected PythonBridgeService $pythonBridge;

    public function __construct(PythonBridgeService $pythonBridge)
    {
        parent::__construct();
        $this->pythonBridge = $pythonBridge;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $symbol = strtoupper($this->argument('symbol'));

        $this->info('═══════════════════════════════════════════');
        $this->info('  XGBoost Model Training');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();

        // Find stock
        $stock = Stock::where('symbol', $symbol)->first();

        if (! $stock) {
            $this->error("Stock not found: {$symbol}");
            $this->info('Please fetch historical data for this stock first using:');
            $this->info("  php artisan fetch:historical-data {$symbol}");

            return self::FAILURE;
        }

        $this->info("Stock: {$stock->name} ({$stock->symbol})");
        $this->newLine();

        // Check if model already exists
        if ($this->pythonBridge->modelExists($stock) && ! $this->option('force')) {
            $this->warn('Model already exists for this stock.');

            if (! $this->confirm('Do you want to retrain the model?', false)) {
                $this->info('Training cancelled.');

                return self::SUCCESS;
            }
        }

        // Parse dates
        try {
            $startDate = $this->option('start-date')
                ? Carbon::parse($this->option('start-date'))
                : Carbon::now()->subYears(2);

            $endDate = $this->option('end-date')
                ? Carbon::parse($this->option('end-date'))
                : Carbon::now();
        } catch (Exception $e) {
            $this->error("Invalid date format: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Check data availability
        $priceCount = $stock->prices()
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        if ($priceCount < 100) {
            $this->error("Insufficient price data: {$priceCount} records found");
            $this->info('At least 100 records are required for training.');
            $this->info('Please fetch more historical data first.');

            return self::FAILURE;
        }

        $this->info("Training data: {$priceCount} records");
        $this->info("Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        $this->newLine();

        // Export data
        $this->info('Step 1: Exporting stock data to CSV...');
        try {
            $csvPath = $this->pythonBridge->exportStockData($stock, $startDate, $endDate);
            $this->info("✓ Data exported: {$csvPath}");
        } catch (Exception $e) {
            $this->error("Failed to export data: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->newLine();

        // Train model
        $this->info('Step 2: Training XGBoost model...');
        $this->info('This may take a few moments...');
        $this->newLine();

        try {
            $result = $this->pythonBridge->trainModel($stock);

            if (! $result['success']) {
                $this->error('Training failed: '.$result['message']);

                return self::FAILURE;
            }

            // Display results
            $this->displayTrainingResults($result);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Training error: {$e->getMessage()}");
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Display training results
     */
    protected function displayTrainingResults(array $result): void
    {
        $this->info('═══════════════════════════════════════════');
        $this->info('  Training Complete!');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();

        // Data summary
        $this->info('Data Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Samples', $result['data_summary']['total_samples']],
                ['Training Samples', $result['data_summary']['train_samples']],
                ['Test Samples', $result['data_summary']['test_samples']],
                ['Number of Features', $result['data_summary']['num_features']],
            ]
        );

        $this->newLine();

        // Training metrics
        $trainMetrics = $result['train_metrics'];
        $this->info('Training Set Performance:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Accuracy', sprintf('%.2f%%', $trainMetrics['accuracy'] * 100)],
                ['Precision', sprintf('%.2f%%', $trainMetrics['precision'] * 100)],
                ['Recall', sprintf('%.2f%%', $trainMetrics['recall'] * 100)],
                ['F1 Score', sprintf('%.4f', $trainMetrics['f1_score'])],
            ]
        );

        $this->newLine();

        // Test metrics
        $testMetrics = $result['test_metrics'];
        $this->info('Test Set Performance:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Accuracy', sprintf('%.2f%%', $testMetrics['accuracy'] * 100)],
                ['Precision', sprintf('%.2f%%', $testMetrics['precision'] * 100)],
                ['Recall', sprintf('%.2f%%', $testMetrics['recall'] * 100)],
                ['F1 Score', sprintf('%.4f', $testMetrics['f1_score'])],
                ['ROC AUC', sprintf('%.4f', $testMetrics['roc_auc'] ?? 0)],
            ]
        );

        $this->newLine();

        // Feature importance (top 10)
        if (! empty($result['feature_importance'])) {
            $this->info('Top 10 Most Important Features:');
            $featureTable = [];
            $rank = 1;
            foreach ($result['feature_importance'] as $feature => $importance) {
                $featureTable[] = [$rank++, $feature, sprintf('%.4f', $importance)];
            }
            $this->table(['Rank', 'Feature', 'Importance'], $featureTable);
            $this->newLine();
        }

        // Model info
        $this->info('Model Details:');
        $this->line("  Version: {$result['model_version']}");
        $this->line("  Trained At: {$result['trained_at']}");
        $this->line("  Model Path: {$result['model_path']}");
        $this->newLine();

        // Overall assessment
        $accuracy = $testMetrics['accuracy'];
        if ($accuracy >= 0.60) {
            $this->info('✓ Model shows promising accuracy for backtesting!');
        } elseif ($accuracy >= 0.55) {
            $this->warn('⚠ Model accuracy is moderate. Consider collecting more data or tuning hyperparameters.');
        } else {
            $this->warn('⚠ Model accuracy is low. Stock price prediction may be challenging for this symbol.');
        }

        $this->newLine();
        $this->info('Next Steps:');
        $this->line("  1. Run backtest: php artisan run:backtest {$result['symbol']}");
        $this->line('  2. View results in the dashboard');
    }
}
