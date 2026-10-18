<?php

declare(strict_types=1);

namespace Amodb\Resource;

use Amodb\Client;

/**
 * Shared helpers for the resource classes. Each resource method is a thin wrapper
 * over Client::request().
 */
abstract class AbstractResource
{
    public function __construct(protected readonly Client $client)
    {
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function fetch(string $path, array $params = [], ?string $language = null): mixed
    {
        return $this->client->request($path, $params, $language);
    }

    /**
     * Percent-encodes one path segment.
     */
    protected static function seg(int|string $value): string
    {
        return rawurlencode((string) $value);
    }

    /**
     * Copies the explicit arguments into the params array. Arguments that are null are skipped,
     * so a value passed in $params stays in place. An explicit argument wins over $params.
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $explicit
     *
     * @return array<string, mixed>
     */
    protected static function merge(array $params, array $explicit): array
    {
        foreach ($explicit as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
