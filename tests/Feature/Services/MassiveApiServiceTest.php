<?php

use App\Exceptions\MassiveApiException;
use App\Services\MassiveApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.massive.api_key', 'test-api-key');
    Config::set('services.massive.base_url', 'https://api.massive.test');
    Config::set('services.massive.rate_limit', 5);
    Cache::flush();
});

test('throws exception when api key is not configured', function () {
    Config::set('services.massive.api_key', null);

    $service = new MassiveApiService;

    expect(fn () => $service->fetchStockInfo('AAPL'))
        ->toThrow(\RuntimeException::class, 'Massive API key is not configured');
});

test('fetches historical prices successfully', function () {
    Http::fake([
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

    $service = new MassiveApiService;
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-02');

    $result = $service->fetchHistoricalPrices('AAPL', $startDate, $endDate);

    expect($result)->toHaveCount(2);
    expect($result[0])->toHaveKeys(['date', 'open', 'high', 'low', 'close', 'volume', 'adjusted_close']);
    expect($result[0]['date'])->toBe('2024-01-01');
    expect($result[0]['close'])->toBe(103.00);
});

test('fetches stock info successfully', function () {
    Http::fake([
        'api.massive.test/stock/AAPL' => Http::response([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc.',
            'exchange' => 'NASDAQ',
        ], 200),
    ]);

    $service = new MassiveApiService;
    $result = $service->fetchStockInfo('AAPL');

    expect($result)->toHaveKeys(['symbol', 'name', 'exchange']);
    expect($result['symbol'])->toBe('AAPL');
    expect($result['name'])->toBe('Apple Inc.');
    expect($result['exchange'])->toBe('NASDAQ');
});

test('throws authentication exception on 401', function () {
    Http::fake([
        'api.massive.test/stock/AAPL' => Http::response([], 401),
    ]);

    $service = new MassiveApiService;

    expect(fn () => $service->fetchStockInfo('AAPL'))
        ->toThrow(MassiveApiException::class, 'authentication failed');
});

test('throws not found exception on 404', function () {
    Http::fake([
        'api.massive.test/stock/INVALID' => Http::response([], 404),
    ]);

    $service = new MassiveApiService;

    expect(fn () => $service->fetchStockInfo('INVALID'))
        ->toThrow(MassiveApiException::class, 'not found');
});

test('throws rate limit exception on 429', function () {
    Http::fake([
        'api.massive.test/stock/AAPL' => Http::response([], 429),
    ]);

    $service = new MassiveApiService;

    expect(fn () => $service->fetchStockInfo('AAPL'))
        ->toThrow(MassiveApiException::class, 'rate limit');
});

test('caches historical prices data', function () {
    Http::fake([
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

    $service = new MassiveApiService;
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-01');

    // First call - should hit API
    $result1 = $service->fetchHistoricalPrices('AAPL', $startDate, $endDate);

    // Second call - should return from cache
    $result2 = $service->fetchHistoricalPrices('AAPL', $startDate, $endDate);

    expect($result1)->toBe($result2);
    Http::assertSentCount(1); // Only one API call should be made
});

test('caches stock info data', function () {
    Http::fake([
        'api.massive.test/stock/AAPL' => Http::response([
            'symbol' => 'AAPL',
            'name' => 'Apple Inc.',
            'exchange' => 'NASDAQ',
        ], 200),
    ]);

    $service = new MassiveApiService;

    // First call - should hit API
    $result1 = $service->fetchStockInfo('AAPL');

    // Second call - should return from cache
    $result2 = $service->fetchStockInfo('AAPL');

    expect($result1)->toBe($result2);
    Http::assertSentCount(1); // Only one API call should be made
});

test('tracks rate limit usage', function () {
    $service = new MassiveApiService;

    $status = $service->getRateLimitStatus();

    expect($status)->toHaveKeys(['current', 'limit', 'resets_at']);
    expect($status['current'])->toBe(0);
    expect($status['limit'])->toBe(5);
});

test('clears rate limit', function () {
    Cache::put('massive_api_rate_limit', 3, now()->addMinute());

    $service = new MassiveApiService;
    $service->clearRateLimit();

    $status = $service->getRateLimitStatus();
    expect($status['current'])->toBe(0);
});

test('filters out incomplete price data', function () {
    Http::fake([
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
                [
                    'date' => '2024-01-02',
                    'open' => null, // Missing required field
                    'high' => 107.00,
                    'low' => 102.00,
                    'close' => 106.00,
                    'volume' => 1200000,
                ],
                [
                    'date' => '2024-01-03',
                    'open' => 106.00,
                    'high' => 108.00,
                    'low' => 105.00,
                    'close' => 107.00,
                    'volume' => 1100000,
                ],
            ],
        ], 200),
    ]);

    $service = new MassiveApiService;
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-03');

    $result = $service->fetchHistoricalPrices('AAPL', $startDate, $endDate);

    // Should only return the 2 valid records
    expect($result)->toHaveCount(2);
});

test('throws exception when api returns invalid response structure', function () {
    Http::fake([
        'api.massive.test/stock/AAPL/historical-prices*' => Http::response([
            'invalid' => 'structure',
        ], 200),
    ]);

    $service = new MassiveApiService;
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-02');

    expect(fn () => $service->fetchHistoricalPrices('AAPL', $startDate, $endDate))
        ->toThrow(MassiveApiException::class, 'missing results array');
});
