<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Http\TransportInterface;
use Amodb\Http\TransportResponse;

/**
 * Transport for tests. Records every request and returns queued responses in order.
 * A queued Throwable is thrown instead of returned.
 */
final class FakeTransport implements TransportInterface
{
    /**
     * @var list<array{method: string, url: string, headers: array<string, string>, timeout: float}>
     */
    public array $requests = [];

    /**
     * @var list<TransportResponse|\Throwable>
     */
    private array $queue = [];

    public function queue(TransportResponse|\Throwable $item): self
    {
        $this->queue[] = $item;

        return $this;
    }

    /**
     * Queues a JSON response.
     *
     * @param array<mixed>         $data
     * @param array<string, string> $headers
     */
    public function queueJson(array $data, int $status = 200, array $headers = []): self
    {
        return $this->queue(new TransportResponse($status, $headers, json_encode($data, JSON_THROW_ON_ERROR)));
    }

    /**
     * Queues a response with a raw body.
     *
     * @param array<string, string> $headers
     */
    public function queueRaw(string $body, int $status = 200, array $headers = []): self
    {
        return $this->queue(new TransportResponse($status, $headers, $body));
    }

    public function send(string $method, string $url, array $headers, float $timeout): TransportResponse
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'timeout' => $timeout,
        ];

        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \LogicException('FakeTransport has no queued response left.');
        }
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, timeout: float}
     */
    public function last(): array
    {
        if ($this->requests === []) {
            throw new \LogicException('FakeTransport recorded no request.');
        }

        return $this->requests[count($this->requests) - 1];
    }
}
