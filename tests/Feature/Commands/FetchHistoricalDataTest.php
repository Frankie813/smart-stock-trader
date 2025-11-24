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
        'api.massive.test/v3/reference/tickers/AAPL' => Http::response([
            'results' => [
                'ticker' => 'AAPL',
                'name' => 'Apple Inc.',
                'primary_exchange' => 'XNAS',
            ],
        ], 200),
        'api.massive.test/v2/aggs/ticker/AAPL/range/1/day/2024-01-01/2024-01-02*' => Http::response([
            'results' => [
                [
                    't' => '2024-01-01',
                    'o' => 100.00,
                    'h' => 105.00,
                    'l' => 99.00,
                    'c' => 103.00,
                    'v' => 1000000,
                ],
                [
                    't' => '2024-01-02',
                    'o' => 103.00,
                    'h' => 107.00,
                    'l' => 102.00,
                    'c' => 106.00,
                    'v' => 1200000,
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
    expect($stock->exchange)->toBe('XNAS');
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
        'api.massive.test/v3/reference/tickers/TSLA' => Http::response([
            'results' => [
                'ticker' => 'TSLA',
                'name' => 'Tesla, Inc.',
                'primary_exchange' => 'XNAS',
            ],
        ], 200),
        'api.massive.test/v2/aggs/ticker/TSLA/*' => Http::response([
            'results' => [
                [
                    't' => '2024-01-01',
                    'o' => 100.00,
                    'h' => 105.00,
                    'l' => 99.00,
                    'c' => 103.00,
                    'v' => 1000000,
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
        'api.massive.test/v2/aggs/ticker/MSFT/*' => Http::response([
            'results' => [
                [
                    't' => '2024-01-01',
                    'o' => 100.00,
                    'h' => 105.00,
                    'l' => 99.00,
                    'c' => 103.00,
                    'v' => 1000000,
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
        'api.massive.test/v2/aggs/ticker/NVDA/*' => Http::response([
            'results' => [
                [
                    't' => '2024-01-01',
                    'o' => 101.00, // Changed
                    'h' => 106.00, // Changed
                    'l' => 99.00,
                    'c' => 104.00, // Changed
                    'v' => 1000000,
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
        'api.massive.test/v2/aggs/ticker/GOOG/*' => Http::response([
            'results' => [
                [
                    't' => '2024-01-01',
                    'o' => 100.00,
                    'h' => 105.00,
                    'l' => 99.00,
                    'c' => 103.00,
                    'v' => 1000000,
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
        'api.massive.test/v3/reference/tickers/FAIL' => Http::response([], 404),
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
        'api.massive.test/v3/reference/tickers/EMPTY' => Http::response([
            'results' => [
                'ticker' => 'EMPTY',
                'name' => 'Empty Stock',
            ],
        ], 200),
        'api.massive.test/v2/aggs/ticker/EMPTY/*' => Http::response([
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
        'api.massive.test/v3/reference/tickers/AAPL' => Http::response([
            'results' => [
                'ticker' => 'AAPL',
                'name' => 'Apple Inc.',
            ],
        ], 200),
        'api.massive.test/v2/aggs/ticker/AAPL/*' => Http::response([
            'results' => [
                [
                    't' => '2024-01-01',
                    'o' => 100.00,
                    'h' => 105.00,
                    'l' => 99.00,
                    'c' => 103.00,
                    'v' => 1000000,
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
