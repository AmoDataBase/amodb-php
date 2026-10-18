<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Watch provider lists: `$client->watchProviders`.
 *
 * For the providers of one title use `$client->movies->watchProviders($id)`
 * or `$client->tv->watchProviders($id)`.
 */
final class WatchProviders extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function regions(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/watch/providers/regions', $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function movie(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/watch/providers/movie', $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function tv(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/watch/providers/tv', $params, $language);
    }
}
