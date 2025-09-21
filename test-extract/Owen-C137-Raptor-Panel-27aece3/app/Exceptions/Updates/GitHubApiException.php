<?php

namespace Pterodactyl\Exceptions\Updates;

/**
 * GitHub API Exception
 * 
 * Thrown when GitHub API interactions fail, including:
 * - API rate limiting
 * - Authentication failures
 * - Network connectivity issues
 * - Invalid API responses
 * - Resource not found errors
 */
class GitHubApiException extends UpdateException
{
    /**
     * @var array|null
     */
    private ?array $responseData = null;

    /**
     * @var int|null
     */
    private ?int $rateLimitRemaining = null;

    /**
     * @var int|null
     */
    private ?int $rateLimitReset = null;

    public function __construct(
        string $message = '', 
        int $statusCode = 0, 
        \Throwable $previous = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, 'GitHub API Error', $statusCode, $previous);
        $this->responseData = $responseData;
        
        // Extract rate limit info from response data if available
        if (isset($responseData['headers'])) {
            $this->rateLimitRemaining = $responseData['headers']['x-ratelimit-remaining'] ?? null;
            $this->rateLimitReset = $responseData['headers']['x-ratelimit-reset'] ?? null;
        }
    }

    /**
     * Get the response data from the GitHub API.
     *
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Get the remaining rate limit requests.
     *
     * @return int|null
     */
    public function getRateLimitRemaining(): ?int
    {
        return $this->rateLimitRemaining;
    }

    /**
     * Get the rate limit reset timestamp.
     *
     * @return int|null
     */
    public function getRateLimitReset(): ?int
    {
        return $this->rateLimitReset;
    }

    /**
     * Check if this error is due to rate limiting.
     *
     * @return bool
     */
    public function isRateLimited(): bool
    {
        return $this->getCode() === 403 && 
               (strpos($this->getMessage(), 'rate limit') !== false || 
                strpos($this->getMessage(), 'API rate limit exceeded') !== false);
    }

    /**
     * Check if this error is due to authentication issues.
     *
     * @return bool
     */
    public function isAuthenticationError(): bool
    {
        return $this->getCode() === 401;
    }

    /**
     * Check if the requested resource was not found.
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return $this->getCode() === 404;
    }

    /**
     * Get a user-friendly error message based on the error type.
     *
     * @return string
     */
    public function getUserMessage(): string
    {
        if ($this->isRateLimited()) {
            return 'GitHub API rate limit exceeded. Please try again later.';
        }

        if ($this->isAuthenticationError()) {
            return 'GitHub API authentication failed. Please check your access token.';
        }

        if ($this->isNotFound()) {
            return 'The requested GitHub resource was not found.';
        }

        return $this->getMessage() ?: 'GitHub API request failed.';
    }

    /**
     * Get context information for logging.
     *
     * @return array
     */
    public function getLogContext(): array
    {
        return array_filter([
            'status_code' => $this->getCode(),
            'rate_limit_remaining' => $this->rateLimitRemaining,
            'rate_limit_reset' => $this->rateLimitReset,
            'response_data' => $this->responseData,
            'is_rate_limited' => $this->isRateLimited(),
            'is_auth_error' => $this->isAuthenticationError(),
            'is_not_found' => $this->isNotFound(),
        ]);
    }
}