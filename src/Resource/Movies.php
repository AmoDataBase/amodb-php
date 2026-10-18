<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Movie endpoints: `$client->movies`.
 *
 * Every method accepts an optional `$params` array for extra query parameters and an
 * optional `$language` tag that overrides the client default.
 * Methods that list results take `$page`.
 */
final class Movies extends AbstractResource
{
    /**
     * @param array<int, string>|string|null $appendToResponse Extra sections, for example ['credits', 'images'].
     * @param array<string, mixed>           $params
     */
    public function details(int|string $id, array|string|null $appendToResponse = null, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/movie/' . self::seg($id), self::merge($params, ['append_to_response' => $appendToResponse]), $language);
    }

    public function credits(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'credits', $params, $language);
    }

    public function images(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'images', $params, $language);
    }

    public function videos(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'videos', $params, $language);
    }

    public function keywords(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'keywords', $params, $language);
    }

    public function externalIds(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'external_ids', $params, $language);
    }

    public function recommendations(int|string $id, ?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'recommendations', self::merge($params, ['page' => $page]), $language);
    }

    public function similar(int|string $id, ?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'similar', self::merge($params, ['page' => $page]), $language);
    }

    public function reviews(int|string $id, ?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'reviews', self::merge($params, ['page' => $page]), $language);
    }

    public function releaseDates(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'release_dates', $params, $language);
    }

    public function watchProviders(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'watch/providers', $params, $language);
    }

    public function translations(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'translations', $params, $language);
    }

    public function alternativeTitles(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'alternative_titles', $params, $language);
    }

    public function popular(?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/movie/popular', self::merge($params, ['page' => $page]), $language);
    }

    public function topRated(?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/movie/top_rated', self::merge($params, ['page' => $page]), $language);
    }

    public function upcoming(?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/movie/upcoming', self::merge($params, ['page' => $page]), $language);
    }

    public function nowPlaying(?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/movie/now_playing', self::merge($params, ['page' => $page]), $language);
    }

    /**
     * Mood meters (amodb extension).
     */
    public function moods(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'moods', $params, $language);
    }

    /**
     * Soundtrack list (amodb extension).
     */
    public function soundtracks(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'soundtracks', $params, $language);
    }

    /**
     * Ratings object (amodb extension).
     */
    public function ratings(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'ratings', $params, $language);
    }

    /**
     * Selected Wikidata facts (amodb extension).
     */
    public function wikidata(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'wikidata', $params, $language);
    }

    /**
     * Cinema showtimes when known (amodb extension).
     *
     * @param string $country Two letter country code, for example "US".
     */
    public function showtimes(int|string $id, string $country, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'showtimes', self::merge($params, ['country' => $country]), $language);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function sub(int|string $id, string $suffix, array $params, ?string $language): mixed
    {
        return $this->fetch('/movie/' . self::seg($id) . '/' . $suffix, $params, $language);
    }
}
