<?php

declare(strict_types=1);

namespace Amodb\Exception;

/**
 * Raised when the API still answers HTTP 429 after the last allowed retry.
 */
class RateLimitException extends AmodbException
{
    public function __construct(
        string $message = '',
        int $status = 429,
        ?int $statusCode = null,
        ?string $statusMessage = null,
        mixed $body = null,
        private readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $statusCode, $statusMessage, $body, $previous);
    }

    /**
     * Seconds from the Retry-After header of the last response, or null when it was missing.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
