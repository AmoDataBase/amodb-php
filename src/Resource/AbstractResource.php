<?php

declare(strict_types=1);

namespace Amodb\Resource;

use Amodb\Client;

/**
 * Shared helpers for the resource classes. Each resource method is a thin wrapper
 * over Client::request().
 *
 * The aliases below describe the amodb extension replies. `Movies` and `Tv` import them
 * for their `@return` tags. In `append_to_response` the moods, ratings and soundtracks
 * sections come without the `id` key.
 *
 * @phpstan-type MoodMeter array{name: string, avg: int|float, values: list<int|float>}
 * @phpstan-type Moods array{id: int, source: 'measured'|'estimated'|null, results: list<MoodMeter>}
 * @phpstan-type Track array{disc: int, number: int, title: string, artist: string, duration_ms: int}
 * @phpstan-type Album array{source: 'musicbrainz'|'deezer', album: string, artist: string|null, released: string|null, url: string|null, track_count: int, tracks: list<Track>}
 * @phpstan-type Soundtracks array{id: int, results: list<Album>}
 * @phpstan-type Rating array{value: int|float, votes: int|null}
 * @phpstan-type Ratings array{id: int, imdb: Rating|null, kinopoisk: Rating|null, metacritic: Rating|null, rotten_tomatoes: Rating|null, tmdb: Rating|null}
 * @phpstan-type WikidataLink array{wikidata_id: string, label: string|null, tmdb_id?: int, media_type?: 'movie'|'tv'}
 * @phpstan-type WikidataEntry array{wikidata_id: string, label: string|null, tmdb_id?: int, media_type?: 'movie'|'tv', year?: int}
 * @phpstan-type WikidataSource array{wikidata_id: string, label: string|null, author: array{wikidata_id: string, label: string|null}|null}
 * @phpstan-type Wikidata array{id: int, wikidata_id: string|null, budget: array{value: int, currency: string|null}|null, box_office: array{value: int, currency: string|null, worldwide: bool}|null, follows: WikidataLink|null, followed_by: WikidataLink|null, sitelinks: array<string, string>, awards: list<WikidataEntry>, nominations: list<WikidataEntry>, based_on: list<WikidataSource>, filming_locations: list<WikidataLink>, narrative_locations: list<WikidataLink>, original_broadcasters: list<WikidataLink>}
 * @phpstan-type Showtime array{date: string, start: string, end: string|null, auditorium: string|null, formats: list<string>, tags: list<string>, audio: string|null, subtitles: string|null, language: string|null, price: int|float|null, currency: string|null, prices: mixed, booking_url: string|null, sold_out: bool}
 * @phpstan-type Venue array{id: int, name: string, chain: array{key: string, name: string|null}, city: string|null, region: string|null, address: string|null, postal_code: string|null, country: string, timezone: string|null, latitude: int|float|null, longitude: int|float|null, phone: string|null, url: string|null, showtimes: list<Showtime>}
 * @phpstan-type ShowtimeCountry array{country: string, name: string, venues: int, cities: int, showtimes: int, first_day: string, last_day: string}
 * @phpstan-type Showtimes array{id: int, country: string|null, results: list<Venue>, countries: list<ShowtimeCountry>}
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
