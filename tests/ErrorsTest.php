<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Exception\AmodbException;
use Amodb\Exception\NetworkException;
use Amodb\Exception\RateLimitException;
use Amodb\Exception\TimeoutException;
use PHPUnit\Framework\Attributes\DataProvider;

final class ErrorsTest extends ClientTestCase
{
    /**
     * @return array<string, array{int, int, string}>
     */
    public static function tmdbErrors(): array
    {
        return [
            'invalid key' => [401, 7, 'Invalid API key: You must be granted a valid key.'],
            'suspended key' => [401, 10, 'Suspended API key: Access to your account has been suspended, contact amodb.'],
            'plan expired' => [402, 36, 'Your plan has expired. Renew at https://amodb.dev/pricing'],
            'not found' => [404, 34, 'The resource you requested could not be found.'],
            'method not offered' => [405, 37, 'This method is not offered by amodb.'],
            'internal error' => [500, 11, 'Internal error: Something went wrong, contact amodb.'],
            'backend timeout' => [504, 24, 'Your request to the backend server timed out. Try again.'],
        ];
    }

    #[DataProvider('tmdbErrors')]
    public function testTmdbErrorBodyIsMappedToTheException(int $status, int $statusCode, string $statusMessage): void
    {
        $body = ['success' => false, 'status_code' => $statusCode, 'status_message' => $statusMessage];
        $transport = (new FakeTransport())->queueJson($body, $status);
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(550);
            $this->fail('Expected an AmodbException.');
        } catch (AmodbException $e) {
            $this->assertNotInstanceOf(RateLimitException::class, $e);
            $this->assertSame($status, $e->getStatus());
            $this->assertSame($statusCode, $e->getStatusCode());
            $this->assertSame($statusMessage, $e->getStatusMessage());
            $this->assertSame($statusMessage, $e->getMessage());
            $this->assertSame($body, $e->getBody());
        }
    }

    public function testRateLimitErrorCarriesTheTmdbFields(): void
    {
        $transport = (new FakeTransport())->queueJson(self::rateLimitBody(), 429, ['Retry-After' => '7']);
        $client = $this->makeClient($transport, ['maxRetries' => 0]);

        try {
            $client->search->movie('fight club');
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertInstanceOf(AmodbException::class, $e);
            $this->assertInstanceOf(\RuntimeException::class, $e);
            $this->assertSame(429, $e->getStatus());
            $this->assertSame(25, $e->getStatusCode());
            $this->assertSame(7, $e->getRetryAfter());
            $this->assertSame(self::rateLimitBody(), $e->getBody());
        }
    }

    public function testNonJsonErrorBodyStillRaisesWithTheHttpStatus(): void
    {
        $transport = (new FakeTransport())->queueRaw('<html>Bad gateway</html>', 502);
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(550);
            $this->fail('Expected an AmodbException.');
        } catch (AmodbException $e) {
            $this->assertSame(502, $e->getStatus());
            $this->assertNull($e->getStatusCode());
            $this->assertSame('HTTP 502', $e->getStatusMessage());
            $this->assertSame('<html>Bad gateway</html>', $e->getBody());
        }
    }

    public function testEmptyErrorBodyStillRaisesWithTheHttpStatus(): void
    {
        $transport = (new FakeTransport())->queueRaw('', 503);
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(550);
            $this->fail('Expected an AmodbException.');
        } catch (AmodbException $e) {
            $this->assertSame(503, $e->getStatus());
            $this->assertNull($e->getStatusCode());
            $this->assertSame('HTTP 503', $e->getStatusMessage());
            $this->assertSame('', $e->getBody());
        }
    }

    public function testNonJsonRateLimitBodyStillRaisesARateLimitException(): void
    {
        $transport = (new FakeTransport())->queueRaw('slow down', 429);
        $client = $this->makeClient($transport, ['maxRetries' => 0]);

        try {
            $client->movies->details(550);
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
            $this->assertNull($e->getStatusCode());
            $this->assertNull($e->getRetryAfter());
            $this->assertSame('slow down', $e->getBody());
        }
    }

    public function testInvalidJsonOnSuccessRaises(): void
    {
        $transport = (new FakeTransport())->queueRaw('not json', 200);
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(550);
            $this->fail('Expected an AmodbException.');
        } catch (AmodbException $e) {
            $this->assertSame(200, $e->getStatus());
            $this->assertNull($e->getStatusCode());
            $this->assertSame('not json', $e->getBody());
            $this->assertInstanceOf(\JsonException::class, $e->getPrevious());
        }
    }

    public function testTimeoutFromTheTransportIsRaisedWithoutRetry(): void
    {
        $transport = (new FakeTransport())->queue(new TimeoutException('Request timed out after 15 seconds.'));
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(550);
            $this->fail('Expected a TimeoutException.');
        } catch (TimeoutException $e) {
            $this->assertInstanceOf(AmodbException::class, $e);
            $this->assertSame(0, $e->getStatus());
            $this->assertNull($e->getStatusCode());
            $this->assertSame('Request timed out after 15 seconds.', $e->getMessage());
        }

        $this->assertSame(1, $transport->requestCount());
        $this->assertSame([], $this->sleeps);
    }

    public function testNetworkErrorFromTheTransportIsRaisedWithoutRetry(): void
    {
        $transport = (new FakeTransport())->queue(new NetworkException('Network error: connection refused'));
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(550);
            $this->fail('Expected a NetworkException.');
        } catch (NetworkException $e) {
            $this->assertInstanceOf(AmodbException::class, $e);
            $this->assertSame(0, $e->getStatus());
        }

        $this->assertSame(1, $transport->requestCount());
        $this->assertSame([], $this->sleeps);
    }

    public function testStatusMessageFallsBackToTheMessage(): void
    {
        $error = new AmodbException('Something failed');

        $this->assertSame('Something failed', $error->getStatusMessage());
        $this->assertSame(0, $error->getStatus());
        $this->assertNull($error->getStatusCode());
        $this->assertNull($error->getBody());
    }
}
