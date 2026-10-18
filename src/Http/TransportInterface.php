<?php

declare(strict_types=1);

namespace Amodb\Http;

/**
 * Sends one HTTP request and returns the response.
 *
 * Implementations must return a TransportResponse for every HTTP status, including 4xx and 5xx.
 * They throw \Amodb\Exception\TimeoutException when the timeout is exceeded and
 * \Amodb\Exception\NetworkException for any other failure before a response arrives.
 */
interface TransportInterface
{
    /**
     * @param string                $method  HTTP method, for example "GET".
     * @param string                $url     Absolute URL including the query string.
     * @param array<string, string> $headers Header name to header value.
     * @param float                 $timeout Seconds for the whole request.
     *
     * @throws \Amodb\Exception\TimeoutException
     * @throws \Amodb\Exception\NetworkException
     */
    public function send(string $method, string $url, array $headers, float $timeout): TransportResponse;
}
