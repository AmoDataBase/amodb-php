<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Search endpoints: `$client->search`.
 *
 * Every method takes the search text, an optional `$params` array (for example `page`,
 * `year`, `first_air_date_year`) and an optional `$language` tag. The `include_adult`
 * parameter is ignored: adult titles and people are never served.
 */
final class Search extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function movie(string $query, array $params = [], ?string $language = null): mixed
    {
        return $this->run('movie', $query, $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function tv(string $query, array $params = [], ?string $language = null): mixed
    {
        return $this->run('tv', $query, $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function person(string $query, array $params = [], ?string $language = null): mixed
    {
        return $this->run('person', $query, $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function multi(string $query, array $params = [], ?string $language = null): mixed
    {
        return $this->run('multi', $query, $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function collection(string $query, array $params = [], ?string $language = null): mixed
    {
        return $this->run('collection', $query, $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function company(string $query, array $params = [], ?string $language = null): mixed
    {
        return $this->run('company', $query, $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function keyword(string $query, array $params = [], ?string $language = null): mixed
    {
        return $this->run('keyword', $query, $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function run(string $kind, string $query, array $params, ?string $language): mixed
    {
        return $this->fetch('/search/' . $kind, self::merge($params, ['query' => $query]), $language);
    }
}
