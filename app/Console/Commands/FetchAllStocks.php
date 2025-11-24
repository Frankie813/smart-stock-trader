<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchAllStocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:all-stocks
                            {--start-date= : Start date (YYYY-MM-DD). Defaults to 2 years ago}
                            {--end-date= : End date (YYYY-MM-DD). Defaults to today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch historical data for all active stocks with rate limiting';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Parse and validate dates
        try {
            $startDate = $this->option('start-date')
                ? Carbon::parse($this->option('start-date'))
                : Carbon::now()->subYears(2);

            $endDate = $this->option('end-date')
                ? Carbon::parse($this->option('end-date'))
                : Carbon::now();
        } catch (\Exception $e) {
            $this->error("Invalid date format: {$e->getMessage()}");
            $this->info('Dates should be in YYYY-MM-DD format');

            return self::FAILURE;
        }

        if ($startDate->isAfter($endDate)) {
            $this->error('Start date must be before end date');

            return self::FAILURE;
        }

        // Fetch all active stocks
        $stocks = Stock::where('is_active', true)
            ->orderBy('symbol')
            ->get();

        if ($stocks->isEmpty()) {
            $this->warn('No active stocks found in the database');
            $this->info('Please add stocks to the database first');

            return self::SUCCESS;
        }

        $totalStocks = $stocks->count();

        $this->info("Found {$totalStocks} active stock(s) to process");
        $this->info("Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        $this->newLine();

        $results = [];
        $currentIndex = 0;

        foreach ($stocks as $stock) {
            $currentIndex++;

            $this->info("Processing Stock {$currentIndex}/{$totalStocks}: {$stock->symbol} ({$stock->name})");
            $this->newLine();

            $startTime = microtime(true);

            // Call the fetch:historical-data command for this stock
            $exitCode = $this->call('fetch:historical-data', [
                'symbol' => $stock->symbol,
                '--start-date' => $startDate->format('Y-m-d'),
                '--end-date' => $endDate->format('Y-m-d'),
            ]);

            $duration = round(microtime(true) - $startTime, 2);

            $results[] = [
                'symbol' => $stock->symbol,
                'name' => $stock->name,
                'status' => $exitCode === self::SUCCESS ? 'Success' : 'Failed',
                'duration' => $duration.' sec',
            ];

            // Rate limiting: Sleep for 12 seconds between API calls (5 calls per minute limit)
            if ($currentIndex < $totalStocks) {
                $this->newLine();
                $this->info('⏱️  Waiting 12 seconds for rate limiting (5 API calls/minute)...');
                sleep(12);
                $this->newLine();
            }
        }

        // Display summary table
        $this->newLine(2);
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                     SUMMARY REPORT');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        $this->table(
            ['Symbol', 'Stock Name', 'Status', 'Duration'],
            array_map(function ($result) {
                return [
                    $result['symbol'],
                    $result['name'],
                    $result['status'] === 'Success' ? '<fg=green>✓ '.$result['status'].'</>' : '<fg=red>✗ '.$result['status'].'</>',
                    $result['duration'],
                ];
            }, $results)
        );

        $successCount = collect($results)->where('status', 'Success')->count();
        $failureCount = collect($results)->where('status', 'Failed')->count();

        $this->newLine();
        $this->info("Total Stocks Processed: {$totalStocks}");
        $this->info("Successful: {$successCount}");

        if ($failureCount > 0) {
            $this->warn("Failed: {$failureCount}");
        }

        $this->newLine();
        $this->info('✓ All stocks processed');

        return self::SUCCESS;
    }
}
