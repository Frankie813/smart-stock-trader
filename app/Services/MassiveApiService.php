<?php

namespace App\Services;

use App\Exceptions\MassiveApiException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MassiveApiService
{
    protected ?string $apiKey;

    protected string $baseUrl;

    protected int $rateLimit;

    protected string $rateLimitKey = 'massive_api_rate_limit';

    public function __construct()
    {
        $this->apiKey = config('services.massive.api_key');
        $this->baseUrl = config('services.massive.base_url', 'https://api.massive.com/v1');
        $this->rateLimit = config('services.massive.rate_limit', 5);
    }

    /**
     * Ensure API key is configured before making requests
     *
     * @throws \RuntimeException
     */
    protected function ensureApiKeyConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Massive API key is not configured. Please set MASSIVE_API_KEY in your .env file.');
        }
    }

    /**
     * Fetch historical OHLCV data for a stock symbol
     *
     * @param  string  $symbol  Stock symbol (e.g., 'AAPL')
     * @param  Carbon  $startDate  Start date for historical data
     * @param  Carbon  $endDate  End date for historical data
     * @return array Array of price data ready for database insertion
     *
     * @throws MassiveApiException
     */
    public function fetchHistoricalPrices(string $symbol, Carbon $startDate, Carbon $endDate): array
    {
        $this->ensureApiKeyConfigured();

        $cacheKey = "massive_prices_{$symbol}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        // Return cached data if available
        if (Cache::has($cacheKey)) {
            Log::channel('massive-api')->info("Returning cached data for {$symbol}", [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);

            return Cache::get($cacheKey);
        }

        $this->checkRateLimit();

        $url = "{$this->baseUrl}/stock/{$symbol}/historical-prices";

        Log::channel('massive-api')->info("Fetching historical prices for {$symbol}", [
            'url' => $url,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        try {
            $response = Http::timeout(30)
                ->retry(3, 100, function ($exception) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get($url, [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d'),
                ]);

            $this->incrementRateLimit();

            if ($response->status() === 401 || $response->status() === 403) {
                Log::channel('massive-api')->error("Authentication failed for {$symbol}");
                throw MassiveApiException::authenticationFailed();
            }

            if ($response->status() === 404) {
                Log::channel('massive-api')->error("Stock {$symbol} not found");
                throw MassiveApiException::notFound($symbol);
            }

            if ($response->status() === 429) {
                Log::channel('massive-api')->error("Rate limit exceeded for {$symbol}");
                throw MassiveApiException::rateLimitExceeded();
            }

            if (! $response->successful()) {
                Log::channel('massive-api')->error("API request failed for {$symbol}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw MassiveApiException::invalidResponse("API request failed with status {$response->status()}");
            }

            $data = $response->json();

            if (! isset($data['results']) || ! is_array($data['results'])) {
                Log::channel('massive-api')->error("Invalid API response structure for {$symbol}", [
                    'response' => $data,
                ]);
                throw MassiveApiException::invalidResponse('API response missing results array');
            }

            $prices = $this->transformPriceData($data['results']);

            // Cache for 1 hour
            Cache::put($cacheKey, $prices, now()->addHour());

            Log::channel('massive-api')->info('Successfully fetched {count} price records for {symbol}', [
                'symbol' => $symbol,
                'count' => count($prices),
            ]);

            return $prices;
        } catch (MassiveApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::channel('massive-api')->error("Network error for {$symbol}", [
                'error' => $e->getMessage(),
            ]);
            throw MassiveApiException::networkError($e->getMessage());
        }
    }

    /**
     * Fetch stock metadata (name, exchange)
     *
     * @param  string  $symbol  Stock symbol (e.g., 'AAPL')
     * @return array Stock information
     *
     * @throws MassiveApiException
     */
    public function fetchStockInfo(string $symbol): array
    {
        $this->ensureApiKeyConfigured();

        $cacheKey = "massive_info_{$symbol}";

        // Return cached data if available
        if (Cache::has($cacheKey)) {
            Log::channel('massive-api')->info("Returning cached stock info for {$symbol}");

            return Cache::get($cacheKey);
        }

        $this->checkRateLimit();

        $url = "{$this->baseUrl}/stock/{$symbol}";

        Log::channel('massive-api')->info("Fetching stock info for {$symbol}", [
            'url' => $url,
        ]);

        try {
            $response = Http::timeout(30)
                ->retry(3, 100, function ($exception) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get($url);

            $this->incrementRateLimit();

            if ($response->status() === 401 || $response->status() === 403) {
                Log::channel('massive-api')->error("Authentication failed for {$symbol}");
                throw MassiveApiException::authenticationFailed();
            }

            if ($response->status() === 404) {
                Log::channel('massive-api')->error("Stock {$symbol} not found");
                throw MassiveApiException::notFound($symbol);
            }

            if ($response->status() === 429) {
                Log::channel('massive-api')->error("Rate limit exceeded for {$symbol}");
                throw MassiveApiException::rateLimitExceeded();
            }

            if (! $response->successful()) {
                Log::channel('massive-api')->error("API request failed for {$symbol}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw MassiveApiException::invalidResponse("API request failed with status {$response->status()}");
            }

            $data = $response->json();

            $stockInfo = [
                'symbol' => $data['symbol'] ?? $symbol,
                'name' => $data['name'] ?? null,
                'exchange' => $data['exchange'] ?? null,
            ];

            // Cache for 24 hours (stock info doesn't change often)
            Cache::put($cacheKey, $stockInfo, now()->addDay());

            Log::channel('massive-api')->info("Successfully fetched stock info for {$symbol}");

            return $stockInfo;
        } catch (MassiveApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::channel('massive-api')->error("Network error for {$symbol}", [
                'error' => $e->getMessage(),
            ]);
            throw MassiveApiException::networkError($e->getMessage());
        }
    }

    /**
     * Transform API price data to database-ready format
     *
     * @param  array  $results  Raw API results
     * @return array Transformed price data
     */
    protected function transformPriceData(array $results): array
    {
        return collect($results)->map(function ($item) {
            return [
                'date' => $item['date'] ?? $item['t'] ?? null,
                'open' => $item['open'] ?? $item['o'] ?? null,
                'high' => $item['high'] ?? $item['h'] ?? null,
                'low' => $item['low'] ?? $item['l'] ?? null,
                'close' => $item['close'] ?? $item['c'] ?? null,
                'volume' => $item['volume'] ?? $item['v'] ?? null,
                'adjusted_close' => $item['adjusted_close'] ?? $item['adjusted'] ?? null,
            ];
        })->filter(function ($item) {
            // Only include records with all required fields
            return $item['date'] && $item['open'] && $item['high'] && $item['low'] && $item['close'];
        })->values()->toArray();
    }

    /**
     * Check if rate limit has been exceeded
     *
     * @throws MassiveApiException
     */
    protected function checkRateLimit(): void
    {
        $count = Cache::get($this->rateLimitKey, 0);

        if ($count >= $this->rateLimit) {
            $ttl = Cache::get($this->rateLimitKey.'_ttl');
            $waitTime = $ttl ? $ttl->diffInSeconds(now()) : 60;

            Log::channel('massive-api')->warning("Rate limit exceeded. Current count: {$count}/{$this->rateLimit}");

            throw MassiveApiException::rateLimitExceeded();
        }
    }

    /**
     * Increment rate limit counter
     */
    protected function incrementRateLimit(): void
    {
        $count = Cache::get($this->rateLimitKey, 0);

        if ($count === 0) {
            $expiresAt = now()->addMinute();
            Cache::put($this->rateLimitKey.'_ttl', $expiresAt, $expiresAt);
            Cache::put($this->rateLimitKey, 1, $expiresAt);
        } else {
            $ttl = Cache::get($this->rateLimitKey.'_ttl');
            Cache::increment($this->rateLimitKey);
        }
    }

    /**
     * Get current rate limit usage
     *
     * @return array Current count and limit
     */
    public function getRateLimitStatus(): array
    {
        return [
            'current' => Cache::get($this->rateLimitKey, 0),
            'limit' => $this->rateLimit,
            'resets_at' => Cache::get($this->rateLimitKey.'_ttl'),
        ];
    }

    /**
     * Clear rate limit counter (useful for testing)
     */
    public function clearRateLimit(): void
    {
        Cache::forget($this->rateLimitKey);
        Cache::forget($this->rateLimitKey.'_ttl');
    }

    /**
     * Wait for rate limit to reset if needed
     */
    public function waitForRateLimit(): void
    {
        $count = Cache::get($this->rateLimitKey, 0);

        if ($count >= $this->rateLimit) {
            $ttl = Cache::get($this->rateLimitKey.'_ttl');

            if ($ttl) {
                $waitTime = $ttl->diffInSeconds(now()) + 1;
                Log::channel('massive-api')->info("Waiting {$waitTime} seconds for rate limit reset");
                sleep($waitTime);
                $this->clearRateLimit();
            }
        }
    }
}
