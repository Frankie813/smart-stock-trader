<?php

namespace App\Jobs;

use App\Models\BacktestResult;
use App\Models\Experiment;
use App\Models\Stock;
use App\Services\PythonBridgeService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunExperiment implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour timeout

    public function __construct(
        public Experiment $experiment
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PythonBridgeService $pythonBridge): void
    {
        try {
            // Mark experiment as started
            $this->experiment->markAsStarted();

            $stockIds = $this->experiment->stock_ids;
            $stockCount = count($stockIds);
            $completedStocks = 0;

            $allResults = [];

            foreach ($stockIds as $stockId) {
                try {
                    $stock = Stock::findOrFail($stockId);

                    Log::info("Running backtest for {$stock->symbol} in experiment #{$this->experiment->id}");

                    // Step 1: Train the model for this stock
                    $trainingResult = $pythonBridge->trainModel($stock);

                    if (! $trainingResult['success']) {
                        Log::error("Training failed for {$stock->symbol}: {$trainingResult['message']}");

                        continue;
                    }

                    // Step 2: Run backtest
                    $backtestResult = $pythonBridge->runBacktest($stock, (float) $this->experiment->initial_capital);

                    if (! $backtestResult['success']) {
                        Log::error("Backtest failed for {$stock->symbol}: {$backtestResult['message']}");

                        continue;
                    }

                    // Step 3: Store backtest results
                    $this->storeBacktestResults($stock, $trainingResult, $backtestResult);

                    $allResults[] = $backtestResult;

                    $completedStocks++;

                    // Update progress
                    $progress = (int) (($completedStocks / $stockCount) * 100);
                    $this->experiment->updateProgress($progress);
                } catch (Exception $e) {
                    Log::error("Error processing stock #{$stockId}: {$e->getMessage()}");

                    continue;
                }
            }

            if (empty($allResults)) {
                throw new Exception('No stocks were successfully backtested');
            }

            // Calculate and store overall results
            $overallResults = $this->calculateOverallResults($allResults);
            $this->experiment->markAsCompleted($overallResults);

            Log::info("Experiment #{$this->experiment->id} completed successfully");
        } catch (Exception $e) {
            Log::error("Experiment #{$this->experiment->id} failed: {$e->getMessage()}");
            $this->experiment->markAsFailed($e->getMessage());
        }
    }

    /**
     * Store backtest results in database
     */
    protected function storeBacktestResults(Stock $stock, array $trainingResult, array $backtestResult): void
    {
        $tradingMetrics = $backtestResult['trading_metrics'];
        $modelMetrics = $backtestResult['prediction_metrics'];

        // Store overall backtest result for this stock
        BacktestResult::create([
            'experiment_id' => $this->experiment->id,
            'stock_id' => $stock->id,
            'model_configuration_id' => $this->experiment->model_configuration_id,
            'start_date' => $this->experiment->start_date,
            'end_date' => $this->experiment->end_date,
            'initial_capital' => $tradingMetrics['initial_capital'] ?? $this->experiment->initial_capital,
            'final_capital' => $tradingMetrics['final_capital'] ?? $this->experiment->initial_capital,
            'total_trades' => $tradingMetrics['total_trades'] ?? 0,
            'winning_trades' => $tradingMetrics['winning_trades'] ?? 0,
            'losing_trades' => $tradingMetrics['losing_trades'] ?? 0,
            'win_rate' => $tradingMetrics['win_rate'] ?? 0,
            'total_return' => $tradingMetrics['total_return_pct'] ?? 0,
            'total_profit_loss' => $tradingMetrics['total_return_dollars'] ?? 0,
            'accuracy_percentage' => ($modelMetrics['accuracy'] ?? 0) * 100,
            'sharpe_ratio' => $tradingMetrics['sharpe_ratio'] ?? null,
            'max_drawdown' => $tradingMetrics['max_drawdown'] ?? null,
            'profit_factor' => $tradingMetrics['profit_factor'] ?? null,
            'avg_profit_per_trade' => $tradingMetrics['avg_profit_per_trade'] ?? 0,
            'avg_loss_per_trade' => $tradingMetrics['avg_loss'] ?? 0,
            'largest_win' => $tradingMetrics['largest_win'] ?? null,
            'largest_loss' => $tradingMetrics['largest_loss'] ?? null,
            'model_version' => $backtestResult['model_version'] ?? '1.0',
        ]);
    }

    /**
     * Calculate overall results from all stock results
     */
    protected function calculateOverallResults(array $allResults): array
    {
        $totalTrades = 0;
        $totalWinningTrades = 0;
        $totalLosingTrades = 0;
        $totalReturn = 0;
        $sharpeRatios = [];
        $accuracies = [];

        foreach ($allResults as $result) {
            $metrics = $result['trading_metrics'];
            $predictionMetrics = $result['prediction_metrics'] ?? [];

            $totalTrades += $metrics['total_trades'] ?? 0;
            $totalWinningTrades += $metrics['winning_trades'] ?? 0;
            $totalLosingTrades += $metrics['losing_trades'] ?? 0;
            $totalReturn += $metrics['total_return_pct'] ?? 0;

            if (isset($metrics['sharpe_ratio'])) {
                $sharpeRatios[] = $metrics['sharpe_ratio'];
            }

            if (isset($predictionMetrics['accuracy'])) {
                $accuracies[] = $predictionMetrics['accuracy'] * 100; // Convert to percentage
            }
        }

        $stockCount = count($allResults);
        $avgReturn = $stockCount > 0 ? $totalReturn / $stockCount : 0;
        $avgSharpe = count($sharpeRatios) > 0 ? array_sum($sharpeRatios) / count($sharpeRatios) : null;
        $avgAccuracy = count($accuracies) > 0 ? array_sum($accuracies) / count($accuracies) : 0;
        $winRate = $totalTrades > 0 ? ($totalWinningTrades / $totalTrades) * 100 : 0;

        return [
            'overall' => [
                'total_trades' => $totalTrades,
                'winning_trades' => $totalWinningTrades,
                'losing_trades' => $totalLosingTrades,
                'win_rate' => round($winRate, 2),
                'accuracy' => round($avgAccuracy, 2),
                'total_return' => round($avgReturn, 2),
                'avg_sharpe_ratio' => $avgSharpe ? round($avgSharpe, 2) : null,
                'stocks_tested' => $stockCount,
            ],
            'per_stock' => array_map(function ($result) {
                $predictionMetrics = $result['prediction_metrics'] ?? [];

                return [
                    'symbol' => $result['stock_symbol'] ?? 'N/A',
                    'return' => $result['trading_metrics']['total_return_pct'] ?? 0,
                    'trades' => $result['trading_metrics']['total_trades'] ?? 0,
                    'win_rate' => $result['trading_metrics']['win_rate'] ?? 0,
                    'accuracy' => isset($predictionMetrics['accuracy']) ? round($predictionMetrics['accuracy'] * 100, 2) : 0,
                ];
            }, $allResults),
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job failed for experiment #{$this->experiment->id}: {$exception->getMessage()}");
        $this->experiment->markAsFailed($exception->getMessage());
    }
}
