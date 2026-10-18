<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * OMDb style lookups: `$client->omdb`.
 *
 * The key is sent as the `apikey` query parameter. An unknown title or id raises an
 * \Amodb\Exception\AmodbException with status 200 and the OMDb `Error` text.
 */
final class Omdb extends AbstractResource
{
    /**
     * Looks up one title by IMDb id.
     *
     * @param string                $imdbId Id such as "tt0137523".
     * @param string|null           $plot   "short" or "full".
     * @param string|null           $type   "movie", "series" or "episode".
     * @param int|string|null       $year   Release year.
     * @param array<string, mixed>  $params Extra query parameters.
     */
    public function byId(string $imdbId, ?string $plot = null, ?string $type = null, int|string|null $year = null, array $params = []): mixed
    {
        return $this->client->omdbRequest(self::merge($params, ['i' => $imdbId, 'plot' => $plot, 'type' => $type, 'y' => $year]));
    }

    /**
     * Looks up one title by exact title.
     *
     * @param string                $title  Title text.
     * @param string|null           $type   "movie", "series" or "episode".
     * @param int|string|null       $year   Release year.
     * @param string|null           $plot   "short" or "full".
     * @param array<string, mixed>  $params Extra query parameters.
     */
    public function byTitle(string $title, ?string $type = null, int|string|null $year = null, ?string $plot = null, array $params = []): mixed
    {
        return $this->client->omdbRequest(self::merge($params, ['t' => $title, 'type' => $type, 'y' => $year, 'plot' => $plot]));
    }

    /**
     * Searches by title text. Results come in pages of 10.
     *
     * @param string                $query  Search text.
     * @param string|null           $type   "movie", "series" or "episode".
     * @param int|string|null       $year   Release year.
     * @param int|null              $page   Page number.
     * @param array<string, mixed>  $params Extra query parameters.
     */
    public function search(string $query, ?string $type = null, int|string|null $year = null, ?int $page = null, array $params = []): mixed
    {
        return $this->client->omdbRequest(self::merge($params, ['s' => $query, 'type' => $type, 'y' => $year, 'page' => $page]));
    }
}
