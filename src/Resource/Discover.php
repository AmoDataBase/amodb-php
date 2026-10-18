<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Discover endpoints: `$client->discover`.
 *
 * Pass any TMDB discover filter in `$params`, for example
 * `['with_genres' => [28, 12], 'sort_by' => 'popularity.desc']`.
 */
final class Discover extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function movie(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/discover/movie', $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function tv(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/discover/tv', $params, $language);
    }
}
