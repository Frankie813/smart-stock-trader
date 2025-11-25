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
    }

    /**
     * Validate that Python environment is available
     */
    protected function validatePythonEnvironment(): void
    {
        if (! file_exists($this->pythonPath)) {
            throw new Exception("Python virtual environment not found at: {$this->pythonPath}. Please set up the Python environment first.");
        }
    }

    /**
     * Extract JSON from Python script output (ignoring log lines)
     */
    protected function extractJsonFromOutput(string $output): ?array
    {
        // Python scripts output logs to stdout, then the final JSON result
        // We need to find where the JSON starts (look for a line starting with {)

        $lines = explode("\n", $output);

        // Find the first line that starts with { (this should be the JSON root)
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if ($line === '{') {
                // Found the start of JSON, take everything from this line onwards
                $jsonString = implode("\n", array_slice($lines, $i));

                // Try to parse it
                $json = json_decode($jsonString, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
                }
            }
        }

        // Fallback: try to find any line with single { and parse from there
        foreach ($lines as $i => $line) {
            if (trim($line) === '{') {
                $jsonString = implode("\n", array_slice($lines, $i));
                $json = json_decode($jsonString, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
                }
            }
        }

        return null;
    }

    /**
     * Export stock data to CSV for Python processing
     */
    public function exportStockData(Stock $stock, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        $this->validatePythonEnvironment();

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
        $this->validatePythonEnvironment();

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

        // Extract JSON from output (skip log lines)
        $result = $this->extractJsonFromOutput($output);

        if ($result === null) {
            // Debug: inspect the actual output structure
            $jsonStart = strrpos($output, '{');
            $jsonPortion = $jsonStart !== false ? substr($output, $jsonStart, 200) : 'No { found';

            Log::error('Failed to parse Python training output', [
                'output_length' => strlen($output),
                'json_start_pos' => $jsonStart,
                'json_portion' => $jsonPortion,
                'last_100_chars' => substr($output, -100),
            ]);
            throw new Exception('Failed to parse Python output: no valid JSON found');
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
        $this->validatePythonEnvironment();

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

        // Extract JSON from output (skip log lines)
        $result = $this->extractJsonFromOutput($output);

        if ($result === null) {
            Log::error('Failed to parse Python output', [
                'output' => $output,
                'error' => 'No valid JSON found in output',
            ]);
            throw new Exception('Failed to parse Python output: no valid JSON found');
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
        $venvExists = file_exists($this->pythonPath);

        if ($venvExists) {
            $command = sprintf('%s --version 2>&1', escapeshellarg($this->pythonPath));
            $version = shell_exec($command);
        } else {
            $version = 'Virtual environment not found';
        }

        return [
            'python_path' => $this->pythonPath,
            'scripts_path' => $this->scriptsPath,
            'python_version' => trim($version ?? 'Unknown'),
            'venv_exists' => $venvExists,
        ];
    }
}
