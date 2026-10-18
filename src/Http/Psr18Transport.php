<?php

declare(strict_types=1);

namespace Amodb\Http;

use Amodb\Exception\NetworkException;
use Amodb\Exception\TimeoutException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Adapter for any PSR-18 client.
 *
 * This class needs psr/http-client and psr/http-factory, which are only suggested by the package.
 * Nothing in the library loads this file, so it is safe to install without them.
 *
 * The timeout argument is ignored because PSR-18 has no per request timeout.
 * Set the timeout on your own client instead.
 */
final class Psr18Transport implements TransportInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
    ) {
    }

    public function send(string $method, string $url, array $headers, float $timeout): TransportResponse
    {
        $request = $this->requestFactory->createRequest($method, $url);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader((string) $name, (string) $value);
        }

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $message = $e->getMessage();
            if (stripos($message, 'timed out') !== false || stripos($message, 'timeout') !== false) {
                throw new TimeoutException('Request timed out: ' . $message, 0, null, null, null, $e);
            }

            throw new NetworkException('Network error: ' . $message, 0, null, null, null, $e);
        }

        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $responseHeaders[(string) $name] = implode(', ', $values);
        }

        return new TransportResponse($response->getStatusCode(), $responseHeaders, (string) $response->getBody());
    }
}
