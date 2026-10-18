<?php

declare(strict_types=1);

namespace Amodb\Http;

/**
 * An HTTP response as seen by the client: status, headers and raw body.
 */
final class TransportResponse
{
    /**
     * Header values keyed by lower case header name.
     *
     * @var array<string, string>
     */
    public readonly array $headers;

    /**
     * @param int                                       $status  HTTP status code.
     * @param array<string, string|list<string>>        $headers Header name to value. Names are lower cased.
     * @param string                                    $body    Raw response body.
     */
    public function __construct(
        public readonly int $status,
        array $headers = [],
        public readonly string $body = '',
    ) {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }
            $normalized[strtolower((string) $name)] = (string) $value;
        }
        $this->headers = $normalized;
    }

    /**
     * Returns a header value by case insensitive name, or null when it is absent.
     */
    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
