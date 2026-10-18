<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Person endpoints: `$client->people`.
 *
 * Every method accepts an optional `$params` array for extra query parameters and an
 * optional `$language` tag that overrides the client default.
 */
final class People extends AbstractResource
{
    /**
     * @param array<int, string>|string|null $appendToResponse Extra sections, for example ['combined_credits'].
     * @param array<string, mixed>           $params
     */
    public function details(int|string $id, array|string|null $appendToResponse = null, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/person/' . self::seg($id), self::merge($params, ['append_to_response' => $appendToResponse]), $language);
    }

    public function combinedCredits(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'combined_credits', $params, $language);
    }

    public function movieCredits(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'movie_credits', $params, $language);
    }

    public function tvCredits(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'tv_credits', $params, $language);
    }

    /**
     * Only `profiles` is filled.
     */
    public function images(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'images', $params, $language);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array{id: int, imdb_id: string|null, wikidata_id: string|null, facebook_id: string|null, instagram_id: string|null, tiktok_id: string|null, twitter_id: string|null, youtube_id: string|null}
     */
    public function externalIds(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'external_ids', $params, $language);
    }

    public function popular(?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/person/popular', self::merge($params, ['page' => $page]), $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function sub(int|string $id, string $suffix, array $params, ?string $language): mixed
    {
        return $this->fetch('/person/' . self::seg($id) . '/' . $suffix, $params, $language);
    }
}
