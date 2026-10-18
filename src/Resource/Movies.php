<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Movie endpoints: `$client->movies`.
 *
 * Every method accepts an optional `$params` array for extra query parameters and an
 * optional `$language` tag that overrides the client default.
 * Methods that list results take `$page`.
 *
 * @phpstan-import-type Moods from AbstractResource
 * @phpstan-import-type Soundtracks from AbstractResource
 * @phpstan-import-type Ratings from AbstractResource
 * @phpstan-import-type Wikidata from AbstractResource
 * @phpstan-import-type Showtimes from AbstractResource
 */
final class Movies extends AbstractResource
{
    /**
     * @param array<int, string>|string|null $appendToResponse Extra sections, for example ['credits', 'images'].
     *                                                         Any of credits, images, videos, keywords, translations,
     *                                                         external_ids, similar, recommendations, reviews,
     *                                                         release_dates, watch/providers, alternative_titles,
     *                                                         lists, moods, soundtracks, ratings, wikidata, showtimes.
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

    /**
     * @param array<string, mixed> $params
     *
     * @return array{id: int, imdb_id: string|null, wikidata_id: string|null, facebook_id: string|null, instagram_id: string|null, twitter_id: string|null, kinopoisk_id?: string|null}
     */
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

    /**
     * Only a few titles have reviews, so `results` is empty for most.
     */
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

    /**
     * The list is often empty. The reply is `{id, titles}`.
     */
    public function alternativeTitles(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'alternative_titles', $params, $language);
    }

    /**
     * User lists that contain the movie.
     */
    public function lists(int|string $id, ?int $page = null, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'lists', self::merge($params, ['page' => $page]), $language);
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
     * The newest movie in the catalog.
     */
    public function latest(array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/movie/latest', $params, $language);
    }

    /**
     * Mood meters (amodb extension).
     *
     * `source` is "measured" for viewer data, "estimated" when derived from genres, and null
     * with empty `results` when the title has no data. `avg` and `values` use a 0 to 10 scale.
     *
     * @param array<string, mixed> $params
     *
     * @return Moods
     */
    public function moods(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'moods', $params, $language);
    }

    /**
     * Soundtrack albums (amodb extension). `results` is empty when no album is known.
     *
     * @param array<string, mixed> $params
     *
     * @return Soundtracks
     */
    public function soundtracks(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'soundtracks', $params, $language);
    }

    /**
     * Ratings from imdb, kinopoisk, metacritic, rotten_tomatoes and tmdb (amodb extension).
     * A provider without a score is null. imdb, kinopoisk and tmdb are out of 10,
     * metacritic and rotten_tomatoes out of 100.
     *
     * @param array<string, mixed> $params
     *
     * @return Ratings
     */
    public function ratings(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'ratings', $params, $language);
    }

    /**
     * Selected Wikidata facts (amodb extension).
     *
     * @param array<string, mixed> $params
     *
     * @return Wikidata
     */
    public function wikidata(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->sub($id, 'wikidata', $params, $language);
    }

    /**
     * Cinema showtimes (amodb extension).
     *
     * Without `$country` the reply has an empty `results` list and a `countries` list with
     * the venue, city and showtime counts per country. With a country code `results` holds
     * the venues, each with its `showtimes`.
     *
     * @param string|null          $country Two letter country code, for example "RU". Optional.
     * @param array<string, mixed> $params
     *
     * @return Showtimes
     */
    public function showtimes(int|string $id, ?string $country = null, array $params = [], ?string $language = null): mixed
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
