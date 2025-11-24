<?php

namespace App\Exceptions;

use Exception;

class MassiveApiException extends Exception
{
    /**
     * Create a new exception instance for rate limiting
     */
    public static function rateLimitExceeded(): self
    {
        return new self('API rate limit exceeded. Please wait before making more requests.');
    }

    /**
     * Create a new exception instance for invalid response
     */
    public static function invalidResponse(string $message = 'Invalid API response'): self
    {
        return new self($message);
    }

    /**
     * Create a new exception instance for authentication failure
     */
    public static function authenticationFailed(): self
    {
        return new self('API authentication failed. Please check your API key.');
    }

    /**
     * Create a new exception instance for network errors
     */
    public static function networkError(string $message): self
    {
        return new self("Network error: {$message}");
    }

    /**
     * Create a new exception instance for not found errors
     */
    public static function notFound(string $symbol): self
    {
        return new self("Stock symbol '{$symbol}' not found.");
    }
}
