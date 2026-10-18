<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Exception\AmodbException;
use Amodb\Exception\RateLimitException;

final class RetryTest extends ClientTestCase
{
    public function testRetriesOn429AndWaitsForRetryAfter(): void
    {
        $transport = (new FakeTransport())
            ->queueJson(self::rateLimitBody(), 429, ['Retry-After' => '2'])
            ->queueJson(self::rateLimitBody(), 429, ['retry-after' => '1'])
            ->queueJson(['id' => 1]);
        $client = $this->makeClient($transport);

        $result = $client->movies->details(1);

        $this->assertSame(['id' => 1], $result);
        $this->assertSame(3, $transport->requestCount());
        $this->assertEquals([2.0, 1.0], $this->sleeps);
        $this->assertSame($transport->requests[0]['url'], $transport->requests[1]['url']);
        $this->assertSame($transport->requests[0]['url'], $transport->requests[2]['url']);
    }

    public function testStopsAfterThreeRetries(): void
    {
        $transport = new FakeTransport();
        for ($i = 0; $i < 5; $i++) {
            $transport->queueJson(self::rateLimitBody(), 429, ['Retry-After' => '3']);
        }
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(1);
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
            $this->assertSame(25, $e->getStatusCode());
            $this->assertSame(3, $e->getRetryAfter());
            $this->assertSame('Your request count (21) is over the allowed limit of (20).', $e->getStatusMessage());
        }

        $this->assertSame(4, $transport->requestCount());
        $this->assertEquals([3.0, 3.0, 3.0], $this->sleeps);
    }

    public function testBacksOffWhenRetryAfterIsMissing(): void
    {
        $transport = new FakeTransport();
        for ($i = 0; $i < 4; $i++) {
            $transport->queueJson(self::rateLimitBody(), 429);
        }
        $client = $this->makeClient($transport);

        try {
            $client->movies->details(1);
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertNull($e->getRetryAfter());
        }

        $this->assertSame(4, $transport->requestCount());
        $this->assertEquals([1.0, 2.0, 4.0], $this->sleeps);
    }

    public function testRetryAfterIsClampedToSixtySeconds(): void
    {
        $transport = (new FakeTransport())
            ->queueJson(self::rateLimitBody(), 429, ['Retry-After' => '500'])
            ->queueJson(['ok' => true]);
        $client = $this->makeClient($transport);

        $client->movies->details(1);

        $this->assertEquals([60.0], $this->sleeps);
    }

    public function testRetryAfterAcceptsAnHttpDate(): void
    {
        $date = gmdate('D, d M Y H:i:s', time() + 30) . ' GMT';
        $transport = (new FakeTransport())
            ->queueJson(self::rateLimitBody(), 429, ['Retry-After' => $date])
            ->queueJson(['ok' => true]);
        $client = $this->makeClient($transport);

        $client->movies->details(1);

        $this->assertCount(1, $this->sleeps);
        $this->assertGreaterThanOrEqual(20.0, $this->sleeps[0]);
        $this->assertLessThanOrEqual(31.0, $this->sleeps[0]);
    }

    public function testAnHttpDateInThePastMeansNoWait(): void
    {
        $date = gmdate('D, d M Y H:i:s', time() - 100) . ' GMT';
        $transport = (new FakeTransport())
            ->queueJson(self::rateLimitBody(), 429, ['Retry-After' => $date])
            ->queueJson(['ok' => true]);
        $client = $this->makeClient($transport);

        $client->movies->details(1);

        $this->assertEquals([0.0], $this->sleeps);
    }

    public function testAnEmptyRetryAfterFallsBackToBackoff(): void
    {
        $transport = (new FakeTransport())
            ->queueJson(self::rateLimitBody(), 429, ['Retry-After' => ''])
            ->queueJson(['ok' => true]);
        $client = $this->makeClient($transport);

        $client->movies->details(1);

        $this->assertEquals([1.0], $this->sleeps);
    }

    public function testMaxRetriesCanBeLowered(): void
    {
        $transport = new FakeTransport();
        $transport->queueJson(self::rateLimitBody(), 429)->queueJson(self::rateLimitBody(), 429);
        $client = $this->makeClient($transport, ['maxRetries' => 1]);

        try {
            $client->movies->details(1);
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
        }

        $this->assertSame(2, $transport->requestCount());
        $this->assertCount(1, $this->sleeps);
    }

    public function testMaxRetriesZeroNeverRetries(): void
    {
        $transport = (new FakeTransport())->queueJson(self::rateLimitBody(), 429);
        $client = $this->makeClient($transport, ['maxRetries' => 0]);

        try {
            $client->movies->details(1);
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
        }

        $this->assertSame(1, $transport->requestCount());
        $this->assertSame([], $this->sleeps);
    }

    public function testMaxRetriesIsCappedAtThree(): void
    {
        $transport = new FakeTransport();
        for ($i = 0; $i < 10; $i++) {
            $transport->queueJson(self::rateLimitBody(), 429, ['Retry-After' => '0']);
        }
        $client = $this->makeClient($transport, ['maxRetries' => 10]);

        try {
            $client->movies->details(1);
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
        }

        $this->assertSame(4, $transport->requestCount());
        $this->assertCount(3, $this->sleeps);
    }

    public function testOtherErrorStatusesAreNotRetried(): void
    {
        foreach ([500, 502, 503, 504, 401, 404] as $status) {
            $transport = (new FakeTransport())->queueJson(['success' => false, 'status_code' => 11, 'status_message' => 'x'], $status);
            $client = $this->makeClient($transport);

            try {
                $client->movies->details(1);
                $this->fail('Expected an AmodbException for status ' . $status . '.');
            } catch (AmodbException $e) {
                $this->assertSame($status, $e->getStatus());
            }

            $this->assertSame(1, $transport->requestCount());
        }

        $this->assertSame([], $this->sleeps);
    }

    public function testRetriesApplyToOmdbAndPlansToo(): void
    {
        $transport = (new FakeTransport())
            ->queueJson([], 429, ['Retry-After' => '1'])
            ->queueJson(['Response' => 'True', 'Title' => 'Fight Club'])
            ->queueJson([], 429, ['Retry-After' => '2'])
            ->queueJson(['plans' => []]);
        $client = $this->makeClient($transport);

        $client->omdb->byId('tt0137523');
        $client->plans();

        $this->assertSame(4, $transport->requestCount());
        $this->assertEquals([1.0, 2.0], $this->sleeps);
    }
}
