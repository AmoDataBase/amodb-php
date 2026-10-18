<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Genre lists: `$client->genres`.
 */
final class Genres extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function movies(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/genre/movie/list', $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function tv(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/genre/tv/list', $params, $language);
    }
}
