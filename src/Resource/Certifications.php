<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Age rating certifications by country: `$client->certifications`.
 */
final class Certifications extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function movie(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/certification/movie/list', $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function tv(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/certification/tv/list', $params, $language);
    }
}
