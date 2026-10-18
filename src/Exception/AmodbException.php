<?php

declare(strict_types=1);

namespace Amodb\Exception;

/**
 * Base class for every error raised by this library.
 *
 * The fields mirror the TMDB style error body:
 *
 * - getStatus(): the HTTP status, or 0 when no response was received.
 * - getStatusCode(): the `status_code` from the error body, or null when absent.
 * - getStatusMessage(): the `status_message` from the error body, or a short fallback text.
 * - getBody(): the parsed JSON body, or the raw text when the body was not JSON.
 *
 * Exception::getCode() is final and returns an int, so the TMDB code lives in getStatusCode().
 */
class AmodbException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly int $status = 0,
        private readonly ?int $statusCode = null,
        private readonly ?string $statusMessage = null,
        private readonly mixed $body = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * HTTP status of the response, or 0 for transport failures.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * The `status_code` field of the TMDB style error body, or null.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * The `status_message` field of the error body, or the exception message.
     */
    public function getStatusMessage(): string
    {
        return $this->statusMessage ?? $this->getMessage();
    }

    /**
     * The parsed JSON body (usually an array), or the raw response text.
     */
    public function getBody(): mixed
    {
        return $this->body;
    }
}
