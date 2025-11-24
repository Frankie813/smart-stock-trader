<?php

namespace App\Console\Commands;

use App\Exceptions\MassiveApiException;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Services\MassiveApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchHistoricalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:historical-data
                            {symbol : The stock symbol (e.g., AAPL)}
                            {--start-date= : Start date (YYYY-MM-DD). Defaults to 2 years ago}
                            {--end-date= : End date (YYYY-MM-DD). Defaults to today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch historical stock price data from Massive.com API';

    protected MassiveApiService $apiService;

    public function __construct(MassiveApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $symbol = strtoupper($this->argument('symbol'));

        // Validate symbol format
        if (! $this->isValidSymbol($symbol)) {
            $this->error("Invalid stock symbol format: {$symbol}");
            $this->info('Stock symbols should be 1-5 uppercase letters (e.g., AAPL, MSFT, TSLA)');

            return self::FAILURE;
        }

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

        $this->info("Fetching historical data for {$symbol}");
        $this->info("Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        $this->newLine();

        // Find or create stock record
        try {
            $stock = $this->findOrCreateStock($symbol);
            $this->info("Stock: {$stock->name} ({$stock->symbol})");

            if ($stock->exchange) {
                $this->info("Exchange: {$stock->exchange}");
            }

            $this->newLine();
        } catch (MassiveApiException $e) {
            $this->error("Failed to fetch stock info: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Fetch and store price data
        try {
            $priceData = $this->apiService->fetchHistoricalPrices($symbol, $startDate, $endDate);

            if (empty($priceData)) {
                $this->warn('No price data returned from API');

                return self::SUCCESS;
            }

            $this->info('Fetched '.count($priceData).' price records from API');
            $this->newLine();

            $stats = $this->storePriceData($stock, $priceData);

            $this->displayResults($stats);

            return self::SUCCESS;
        } catch (MassiveApiException $e) {
            $this->error("API Error: {$e->getMessage()}");

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Validate stock symbol format
     */
    protected function isValidSymbol(string $symbol): bool
    {
        return preg_match('/^[A-Z]{1,5}$/', $symbol);
    }

    /**
     * Find existing stock or create new one
     */
    protected function findOrCreateStock(string $symbol): Stock
    {
        $stock = Stock::where('symbol', $symbol)->first();

        if ($stock) {
            $this->info('Found existing stock record');

            return $stock;
        }

        $this->info('Creating new stock record...');

        // Fetch stock info from API
        $stockInfo = $this->apiService->fetchStockInfo($symbol);

        $stock = Stock::create([
            'symbol' => $symbol,
            'name' => $stockInfo['name'] ?? $symbol,
            'exchange' => $stockInfo['exchange'] ?? null,
            'is_active' => true,
        ]);

        $this->info('Created stock record');

        return $stock;
    }

    /**
     * Store price data in database
     */
    protected function storePriceData(Stock $stock, array $priceData): array
    {
        $newRecords = 0;
        $updatedRecords = 0;
        $skippedRecords = 0;

        $this->info('Storing price data in database...');

        $progressBar = $this->output->createProgressBar(count($priceData));
        $progressBar->start();

        foreach ($priceData as $data) {
            try {
                DB::beginTransaction();

                $existingPrice = StockPrice::where('stock_id', $stock->id)
                    ->where('date', $data['date'])
                    ->first();

                if ($existingPrice) {
                    // Check if data has changed
                    $hasChanged = $existingPrice->open != $data['open']
                        || $existingPrice->high != $data['high']
                        || $existingPrice->low != $data['low']
                        || $existingPrice->close != $data['close']
                        || $existingPrice->volume != $data['volume'];

                    if ($hasChanged) {
                        $existingPrice->update([
                            'open' => $data['open'],
                            'high' => $data['high'],
                            'low' => $data['low'],
                            'close' => $data['close'],
                            'volume' => $data['volume'],
                            'adjusted_close' => $data['adjusted_close'],
                        ]);
                        $updatedRecords++;
                    } else {
                        $skippedRecords++;
                    }
                } else {
                    StockPrice::create([
                        'stock_id' => $stock->id,
                        'date' => $data['date'],
                        'open' => $data['open'],
                        'high' => $data['high'],
                        'low' => $data['low'],
                        'close' => $data['close'],
                        'volume' => $data['volume'],
                        'adjusted_close' => $data['adjusted_close'],
                    ]);
                    $newRecords++;
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->warn("Failed to store price data for {$data['date']}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        return [
            'total' => count($priceData),
            'new' => $newRecords,
            'updated' => $updatedRecords,
            'skipped' => $skippedRecords,
        ];
    }

    /**
     * Display results summary
     */
    protected function displayResults(array $stats): void
    {
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records Fetched', $stats['total']],
                ['New Records Created', $stats['new']],
                ['Records Updated', $stats['updated']],
                ['Records Skipped (No Change)', $stats['skipped']],
            ]
        );

        if ($stats['new'] > 0 || $stats['updated'] > 0) {
            $this->info('✓ Historical data fetched and stored successfully');
        } else {
            $this->warn('No new data was added');
        }
    }
}
