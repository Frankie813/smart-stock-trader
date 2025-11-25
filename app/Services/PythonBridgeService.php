<?php

namespace App\Services;

use App\Models\Stock;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class PythonBridgeService
{
    protected string $pythonPath;

    protected string $scriptsPath;

    public function __construct()
    {
        $this->pythonPath = base_path('python/venv/bin/python');
        $this->scriptsPath = base_path('python');

        // Validate Python path
        if (! file_exists($this->pythonPath)) {
            throw new Exception("Python virtual environment not found at: {$this->pythonPath}");
        }
    }

    /**
     * Export stock data to CSV for Python processing
     */
    public function exportStockData(Stock $stock, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        $startDate = $startDate ?? Carbon::now()->subYears(2);
        $endDate = $endDate ?? Carbon::now();

        Log::info("Exporting stock data for {$stock->symbol}", [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        // Fetch price data
        $prices = $stock->prices()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        if ($prices->isEmpty()) {
            throw new Exception("No price data found for {$stock->symbol} in the specified date range");
        }

        // Prepare CSV file path
        $csvFilename = "{$stock->symbol}_data.csv";
        $csvPath = base_path("python/data/{$csvFilename}");

        // Ensure directory exists
        $directory = dirname($csvPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Open file for writing
        $handle = fopen($csvPath, 'w');

        if (! $handle) {
            throw new Exception("Failed to create CSV file: {$csvPath}");
        }

        // Write header
        fputcsv($handle, ['date', 'open', 'high', 'low', 'close', 'volume', 'adjusted_close']);

        // Write data rows
        foreach ($prices as $price) {
            fputcsv($handle, [
                $price->date,
                $price->open,
                $price->high,
                $price->low,
                $price->close,
                $price->volume,
                $price->adjusted_close ?? $price->close,
            ]);
        }

        fclose($handle);

        Log::info("Exported {$prices->count()} records to {$csvPath}");

        return $csvPath;
    }

    /**
     * Train XGBoost model for a stock
     */
    public function trainModel(Stock $stock, array $config = []): array
    {
        Log::info("Training model for {$stock->symbol}");

        // Export data first
        $csvPath = $this->exportStockData($stock);

        // Build Python command
        $scriptPath = "{$this->scriptsPath}/train_model.py";
        $command = sprintf(
            '%s %s %s 2>&1',
            escapeshellarg($this->pythonPath),
            escapeshellarg($scriptPath),
            escapeshellarg($stock->symbol)
        );

        Log::debug("Executing Python command: {$command}");

        // Execute Python script
        $output = shell_exec($command);

        if ($output === null) {
            throw new Exception('Failed to execute Python training script');
        }

        // Parse JSON response
        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse Python output', [
                'output' => $output,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception("Failed to parse Python output: {$output}");
        }

        // Check for errors
        if (! ($result['success'] ?? false)) {
            $errorMsg = $result['message'] ?? 'Unknown error occurred';
            Log::error("Training failed for {$stock->symbol}", ['error' => $errorMsg]);
            throw new Exception("Training failed: {$errorMsg}");
        }

        Log::info("Model trained successfully for {$stock->symbol}", [
            'accuracy' => $result['test_metrics']['accuracy'] ?? 'N/A',
        ]);

        return $result;
    }

    /**
     * Run backtest for a stock
     */
    public function runBacktest(Stock $stock, float $initialCapital = 10000.0): array
    {
        Log::info("Running backtest for {$stock->symbol}");

        // Build Python command
        $scriptPath = "{$this->scriptsPath}/backtest.py";
        $command = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellarg($this->pythonPath),
            escapeshellarg($scriptPath),
            escapeshellarg($stock->symbol),
            escapeshellarg((string) $initialCapital)
        );

        Log::debug("Executing Python command: {$command}");

        // Execute Python script
        $output = shell_exec($command);

        if ($output === null) {
            throw new Exception('Failed to execute Python backtest script');
        }

        // Parse JSON response
        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse Python output', [
                'output' => $output,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception("Failed to parse Python output: {$output}");
        }

        // Check for errors
        if (! ($result['success'] ?? false)) {
            $errorMsg = $result['message'] ?? 'Unknown error occurred';
            Log::error("Backtest failed for {$stock->symbol}", ['error' => $errorMsg]);
            throw new Exception("Backtest failed: {$errorMsg}");
        }

        Log::info("Backtest completed successfully for {$stock->symbol}", [
            'total_return' => $result['trading_metrics']['total_return_pct'] ?? 'N/A',
            'win_rate' => $result['trading_metrics']['win_rate'] ?? 'N/A',
        ]);

        return $result;
    }

    /**
     * Check if model exists for a stock
     */
    public function modelExists(Stock $stock): bool
    {
        $modelPath = base_path("python/models/{$stock->symbol}_model.pkl");

        return file_exists($modelPath);
    }

    /**
     * Get model file path for a stock
     */
    public function getModelPath(Stock $stock): string
    {
        return base_path("python/models/{$stock->symbol}_model.pkl");
    }

    /**
     * Delete model for a stock
     */
    public function deleteModel(Stock $stock): bool
    {
        $modelPath = $this->getModelPath($stock);

        if (file_exists($modelPath)) {
            return unlink($modelPath);
        }

        return false;
    }

    /**
     * Get Python environment info
     */
    public function getPythonInfo(): array
    {
        $command = sprintf('%s --version 2>&1', escapeshellarg($this->pythonPath));
        $version = shell_exec($command);

        return [
            'python_path' => $this->pythonPath,
            'scripts_path' => $this->scriptsPath,
            'python_version' => trim($version ?? 'Unknown'),
            'venv_exists' => file_exists($this->pythonPath),
        ];
    }
}
