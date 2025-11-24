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
        'api.massive.test/v3/reference/tickers/AAPL' => Http::response([
            'results' => [
                'ticker' => 'AAPL',
                'name' => 'Apple Inc.',
                'primary_exchange' => 'XNAS',
            ],
        ], 200),
    ]);

    $service = new MassiveApiService;
    $result = $service->fetchStockInfo('AAPL');

    expect($result)->toHaveKeys(['symbol', 'name', 'exchange']);
    expect($result['symbol'])->toBe('AAPL');
    expect($result['name'])->toBe('Apple Inc.');
    expect($result['exchange'])->toBe('XNAS');
});

test('throws authentication exception on 401', function () {
    Http::fake([
        'api.massive.test/v3/reference/tickers/AAPL' => Http::response([], 401),
    ]);

    $service = new MassiveApiService;

    expect(fn () => $service->fetchStockInfo('AAPL'))
        ->toThrow(MassiveApiException::class, 'authentication failed');
});

test('throws not found exception on 404', function () {
    Http::fake([
        'api.massive.test/v3/reference/tickers/INVALID' => Http::response([], 404),
    ]);

    $service = new MassiveApiService;

    expect(fn () => $service->fetchStockInfo('INVALID'))
        ->toThrow(MassiveApiException::class, 'not found');
});

test('throws rate limit exception on 429', function () {
    Http::fake([
        'api.massive.test/v3/reference/tickers/AAPL' => Http::response([], 429),
    ]);

    $service = new MassiveApiService;

    expect(fn () => $service->fetchStockInfo('AAPL'))
        ->toThrow(MassiveApiException::class, 'rate limit');
});

test('caches historical prices data', function () {
    Http::fake([
        'api.massive.test/v2/aggs/ticker/AAPL/range/1/day/2024-01-01/2024-01-01*' => Http::response([
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
        'api.massive.test/v3/reference/tickers/AAPL' => Http::response([
            'results' => [
                'ticker' => 'AAPL',
                'name' => 'Apple Inc.',
                'primary_exchange' => 'XNAS',
            ],
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
        'api.massive.test/v2/aggs/ticker/AAPL/range/1/day/2024-01-01/2024-01-03*' => Http::response([
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
                    'o' => null, // Missing required field
                    'h' => 107.00,
                    'l' => 102.00,
                    'c' => 106.00,
                    'v' => 1200000,
                ],
                [
                    't' => '2024-01-03',
                    'o' => 106.00,
                    'h' => 108.00,
                    'l' => 105.00,
                    'c' => 107.00,
                    'v' => 1100000,
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
        'api.massive.test/v2/aggs/ticker/AAPL/range/1/day/2024-01-01/2024-01-02*' => Http::response([
            'invalid' => 'structure',
        ], 200),
    ]);

    $service = new MassiveApiService;
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-02');

    expect(fn () => $service->fetchHistoricalPrices('AAPL', $startDate, $endDate))
        ->toThrow(MassiveApiException::class, 'missing results array');
});
