<?php

use App\Models\Stock;
use App\Models\StockPrice;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Config::set('services.massive.api_key', 'test-api-key');
    Config::set('services.massive.base_url', 'https://api.massive.test');
    Config::set('services.massive.rate_limit', 5);
});

test('fetches historical data successfully', function () {
    Http::fake([
        'api.massive.test/stock/AAPL' => Http::response([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc.',
            'exchange' => 'NASDAQ',
        ], 200),
        'api.massive.test/stock/AAPL/historical-prices*' => Http::response([
            'results' => [
                [
                    'date' => '2024-01-01',
                    'open' => 100.00,
                    'high' => 105.00,
                    'low' => 99.00,
                    'close' => 103.00,
                    'volume' => 1000000,
                    'adjusted_close' => 103.00,
                ],
                [
                    'date' => '2024-01-02',
                    'open' => 103.00,
                    'high' => 107.00,
                    'low' => 102.00,
                    'close' => 106.00,
                    'volume' => 1200000,
                    'adjusted_close' => 106.00,
                ],
            ],
        ], 200),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'AAPL',
        '--start-date' => '2024-01-01',
        '--end-date' => '2024-01-02',
    ])->assertSuccessful();

    expect(Stock::where('symbol', 'AAPL')->exists())->toBeTrue();
    expect(StockPrice::count())->toBe(2);

    $stock = Stock::where('symbol', 'AAPL')->first();
    expect($stock->name)->toBe('Apple Inc.');
    expect($stock->exchange)->toBe('NASDAQ');
});

test('validates symbol format', function () {
    artisan('fetch:historical-data', [
        'symbol' => 'invalid-symbol',
    ])->assertFailed();

    expect(Stock::count())->toBe(0);
});

test('validates date format', function () {
    artisan('fetch:historical-data', [
        'symbol' => 'AAPL',
        '--start-date' => 'invalid-date',
    ])->assertFailed();
});

test('validates start date before end date', function () {
    artisan('fetch:historical-data', [
        'symbol' => 'AAPL',
        '--start-date' => '2024-12-31',
        '--end-date' => '2024-01-01',
    ])->assertFailed();
});

test('uses default dates when not provided', function () {
    Http::fake([
        'api.massive.test/stock/TSLA' => Http::response([
            'symbol' => 'TSLA',
            'name' => 'Tesla, Inc.',
            'exchange' => 'NASDAQ',
        ], 200),
        'api.massive.test/stock/TSLA/historical-prices*' => Http::response([
            'results' => [
                [
                    'date' => '2024-01-01',
                    'open' => 100.00,
                    'high' => 105.00,
                    'low' => 99.00,
                    'close' => 103.00,
                    'volume' => 1000000,
                ],
            ],
        ], 200),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'TSLA',
    ])->assertSuccessful();

    expect(Stock::where('symbol', 'TSLA')->exists())->toBeTrue();
});

test('uses existing stock record', function () {
    $stock = Stock::factory()->create([
        'symbol' => 'MSFT',
        'name' => 'Microsoft Corporation',
        'exchange' => 'NASDAQ',
    ]);

    Http::fake([
        'api.massive.test/stock/MSFT/historical-prices*' => Http::response([
            'results' => [
                [
                    'date' => '2024-01-01',
                    'open' => 100.00,
                    'high' => 105.00,
                    'low' => 99.00,
                    'close' => 103.00,
                    'volume' => 1000000,
                ],
            ],
        ], 200),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'MSFT',
        '--start-date' => '2024-01-01',
        '--end-date' => '2024-01-01',
    ])->assertSuccessful();

    expect(Stock::where('symbol', 'MSFT')->count())->toBe(1);
    expect(StockPrice::where('stock_id', $stock->id)->count())->toBe(1);
});

test('updates existing price records when data changes', function () {
    $stock = Stock::factory()->create(['symbol' => 'NVDA']);

    StockPrice::factory()->create([
        'stock_id' => $stock->id,
        'date' => '2024-01-01',
        'open' => 100.00,
        'high' => 105.00,
        'low' => 99.00,
        'close' => 103.00,
        'volume' => 1000000,
    ]);

    Http::fake([
        'api.massive.test/stock/NVDA/historical-prices*' => Http::response([
            'results' => [
                [
                    'date' => '2024-01-01',
                    'open' => 101.00, // Changed
                    'high' => 106.00, // Changed
                    'low' => 99.00,
                    'close' => 104.00, // Changed
                    'volume' => 1000000,
                    'adjusted_close' => 104.00,
                ],
            ],
        ], 200),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'NVDA',
        '--start-date' => '2024-01-01',
        '--end-date' => '2024-01-01',
    ])->assertSuccessful();

    $price = StockPrice::where('stock_id', $stock->id)->where('date', '2024-01-01')->first();
    expect($price->open)->toBe('101.0000');
    expect($price->close)->toBe('104.0000');
    expect(StockPrice::count())->toBe(1); // Should still be 1 record
});

test('skips records with no changes', function () {
    $stock = Stock::factory()->create(['symbol' => 'GOOG']);

    StockPrice::factory()->create([
        'stock_id' => $stock->id,
        'date' => '2024-01-01',
        'open' => 100.00,
        'high' => 105.00,
        'low' => 99.00,
        'close' => 103.00,
        'volume' => 1000000,
    ]);

    Http::fake([
        'api.massive.test/stock/GOOG/historical-prices*' => Http::response([
            'results' => [
                [
                    'date' => '2024-01-01',
                    'open' => 100.00,
                    'high' => 105.00,
                    'low' => 99.00,
                    'close' => 103.00,
                    'volume' => 1000000,
                ],
            ],
        ], 200),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'GOOG',
        '--start-date' => '2024-01-01',
        '--end-date' => '2024-01-01',
    ])->assertSuccessful();

    expect(StockPrice::count())->toBe(1);
});

test('handles api errors gracefully', function () {
    Http::fake([
        'api.massive.test/stock/FAIL' => Http::response([], 404),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'FAIL',
        '--start-date' => '2024-01-01',
        '--end-date' => '2024-01-02',
    ])->assertFailed();

    expect(Stock::where('symbol', 'FAIL')->exists())->toBeFalse();
});

test('handles empty api response', function () {
    Http::fake([
        'api.massive.test/stock/EMPTY' => Http::response([
            'symbol' => 'EMPTY',
            'name' => 'Empty Stock',
        ], 200),
        'api.massive.test/stock/EMPTY/historical-prices*' => Http::response([
            'results' => [],
        ], 200),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'EMPTY',
        '--start-date' => '2024-01-01',
        '--end-date' => '2024-01-02',
    ])->assertSuccessful();

    expect(Stock::where('symbol', 'EMPTY')->exists())->toBeTrue();
    expect(StockPrice::count())->toBe(0);
});

test('converts symbol to uppercase', function () {
    Http::fake([
        'api.massive.test/stock/AAPL' => Http::response([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc.',
        ], 200),
        'api.massive.test/stock/AAPL/historical-prices*' => Http::response([
            'results' => [
                [
                    'date' => '2024-01-01',
                    'open' => 100.00,
                    'high' => 105.00,
                    'low' => 99.00,
                    'close' => 103.00,
                    'volume' => 1000000,
                ],
            ],
        ], 200),
    ]);

    artisan('fetch:historical-data', [
        'symbol' => 'aapl', // lowercase
        '--start-date' => '2024-01-01',
        '--end-date' => '2024-01-01',
    ])->assertSuccessful();

    expect(Stock::where('symbol', 'AAPL')->exists())->toBeTrue();
});
