<?php

declare(strict_types=1);

namespace Amodb\Http;

use Amodb\Exception\NetworkException;
use Amodb\Exception\TimeoutException;

/**
 * Default transport. Uses ext-curl and never follows redirects.
 *
 * Messages of the exceptions it throws never contain the request URL, so a key sent
 * in the query string cannot end up in logs through an error message.
 */
final class CurlTransport implements TransportInterface
{
    /** The libcurl error number for a timed out operation. */
    private const CURLE_OPERATION_TIMEDOUT = 28;

    public function send(string $method, string $url, array $headers, float $timeout): TransportResponse
    {
        if (!function_exists('curl_init')) {
            throw new NetworkException('The curl extension is not available.');
        }

        $handle = curl_init();
        if ($handle === false) {
            throw new NetworkException('Could not start a curl session.');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $responseHeaders = [];
        $milliseconds = max(1, (int) ceil($timeout * 1000));

        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING => '',
            CURLOPT_NOSIGNAL => true,
            CURLOPT_CONNECTTIMEOUT_MS => $milliseconds,
            CURLOPT_TIMEOUT_MS => $milliseconds,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                if (str_starts_with($line, 'HTTP/')) {
                    // A new status line starts a new header block.
                    $responseHeaders = [];

                    return $length;
                }
                $colon = strpos($line, ':');
                if ($colon !== false) {
                    $name = strtolower(trim(substr($line, 0, $colon)));
                    $value = trim(substr($line, $colon + 1));
                    if ($name !== '') {
                        $responseHeaders[$name] = isset($responseHeaders[$name])
                            ? $responseHeaders[$name] . ', ' . $value
                            : $value;
                    }
                }

                return $length;
            },
        ]);

        $body = curl_exec($handle);

        if ($body === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            if ($errno === self::CURLE_OPERATION_TIMEDOUT) {
                throw new TimeoutException('Request timed out after ' . $timeout . ' seconds.');
            }

            throw new NetworkException(sprintf('Network error: %s (curl error %d).', $error, $errno));
        }

        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        return new TransportResponse($status, $responseHeaders, (string) $body);
    }
}
