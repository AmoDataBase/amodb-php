<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Client;
use PHPUnit\Framework\TestCase;

/**
 * Base class for the tests. It builds a client on a FakeTransport and records the
 * seconds the client asks to sleep, so no test waits.
 */
abstract class ClientTestCase extends TestCase
{
    protected const KEY = '0123456789abcdef0123456789abcdef';

    /**
     * Seconds passed to the injected sleep function.
     *
     * @var list<float>
     */
    protected array $sleeps = [];

    /**
     * @param array<string, mixed> $options
     */
    protected function makeClient(FakeTransport $transport, array $options = []): Client
    {
        $options['transport'] = $transport;
        $options['sleep'] = function (float $seconds): void {
            $this->sleeps[] = $seconds;
        };

        return new Client(self::KEY, $options);
    }

    /**
     * Parses the query string of a URL into an array.
     *
     * @return array<string, mixed>
     */
    protected static function queryOf(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        $result = [];
        if (is_string($query) && $query !== '') {
            parse_str($query, $result);
        }

        return $result;
    }

    /**
     * Returns the path of a URL.
     */
    protected static function pathOf(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }

    /**
     * A TMDB style rate limit body.
     *
     * @return array<string, mixed>
     */
    protected static function rateLimitBody(): array
    {
        return [
            'success' => false,
            'status_code' => 25,
            'status_message' => 'Your request count (21) is over the allowed limit of (20).',
        ];
    }
}
