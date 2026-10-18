<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Exception\AmodbException;

/**
 * The amodb extension endpoints: moods, soundtracks, ratings, wikidata, showtimes,
 * find by external id and external ids. The bodies are trimmed copies of real replies.
 */
final class ExtensionsTest extends ClientTestCase
{
    public function testMeasuredMoodsUseAZeroToTenScale(): void
    {
        $transport = (new FakeTransport())->queueJson(self::measuredMoods());
        $client = $this->makeClient($transport);

        $moods = $client->movies->moods(155);

        $this->assertSame('/3/movie/155/moods', self::pathOf($transport->last()['url']));
        $this->assertSame(155, $moods['id']);
        $this->assertSame('measured', $moods['source']);
        $this->assertSame('action', $moods['results'][0]['name']);
        $this->assertSame(3.9, $moods['results'][0]['avg']);
        $this->assertCount(6, $moods['results'][0]['values']);
        $this->assertArrayNotHasKey('value', $moods['results'][0]);
    }

    public function testEstimatedMoodsOnATvShowHaveIntegerScores(): void
    {
        $transport = (new FakeTransport())->queueJson(self::estimatedTvMoods());
        $client = $this->makeClient($transport);

        $moods = $client->tv->moods(1396);

        $this->assertSame('/3/tv/1396/moods', self::pathOf($transport->last()['url']));
        $this->assertSame('estimated', $moods['source']);
        $this->assertSame(4, $moods['results'][0]['avg']);
        $this->assertSame([3, 4, 5, 4, 3], $moods['results'][0]['values']);
    }

    public function testMoodsWithoutDataHaveANullSource(): void
    {
        $body = self::emptyMoods();
        $client = $this->makeClient((new FakeTransport())->queueJson($body));

        $moods = $client->movies->moods(1798888);

        $this->assertNull($moods['source']);
        $this->assertSame([], $moods['results']);
        $this->assertSame($body, $moods);
    }

    public function testSoundtracksReturnAlbumsWithTracks(): void
    {
        $transport = (new FakeTransport())->queueJson(self::musicbrainzSoundtrack());
        $client = $this->makeClient($transport);

        $soundtracks = $client->movies->soundtracks(155);

        $this->assertSame('/3/movie/155/soundtracks', self::pathOf($transport->last()['url']));
        $album = $soundtracks['results'][0];
        $this->assertSame('musicbrainz', $album['source']);
        $this->assertSame('The Dark Knight', $album['album']);
        $this->assertSame('2008-07-15', $album['released']);
        $this->assertSame(14, $album['track_count']);
        $this->assertSame('Why So Serious?', $album['tracks'][0]['title']);
        $this->assertSame(554000, $album['tracks'][0]['duration_ms']);
        $this->assertSame(1, $album['tracks'][1]['disc']);
        $this->assertSame(2, $album['tracks'][1]['number']);
    }

    public function testSoundtrackAlbumFromDeezer(): void
    {
        $client = $this->makeClient((new FakeTransport())->queueJson(self::deezerSoundtrack()));

        $soundtracks = $client->movies->soundtracks(27205);

        $this->assertSame('deezer', $soundtracks['results'][0]['source']);
        $this->assertSame('Hans Zimmer', $soundtracks['results'][0]['artist']);
        $this->assertSame('https://www.deezer.com/album/601778', $soundtracks['results'][0]['url']);
    }

    public function testSoundtrackAlbumCanHaveNoArtist(): void
    {
        $client = $this->makeClient((new FakeTransport())->queueJson(self::variousArtistsSoundtrack()));

        $soundtracks = $client->movies->soundtracks(13);

        $this->assertNull($soundtracks['results'][0]['artist']);
        $this->assertSame('Elvis Presley', $soundtracks['results'][0]['tracks'][0]['artist']);
    }

    public function testSoundtracksAreEmptyWhenNoAlbumIsKnown(): void
    {
        $transport = (new FakeTransport())->queueJson(['id' => 1396, 'results' => []]);
        $client = $this->makeClient($transport);

        $soundtracks = $client->tv->soundtracks(1396);

        $this->assertSame('/3/tv/1396/soundtracks', self::pathOf($transport->last()['url']));
        $this->assertSame(['id' => 1396, 'results' => []], $soundtracks);
    }

    public function testRatingsAreValueAndVotesPairs(): void
    {
        $transport = (new FakeTransport())->queueJson(self::movieRatings());
        $client = $this->makeClient($transport);

        $ratings = $client->movies->ratings(550);

        $this->assertSame('/3/movie/550/ratings', self::pathOf($transport->last()['url']));
        $this->assertSame(['id', 'imdb', 'kinopoisk', 'metacritic', 'rotten_tomatoes', 'tmdb'], array_keys($ratings));
        $this->assertSame(['value' => 8.8, 'votes' => 2669989], $ratings['imdb']);
        $this->assertSame(['value' => 8.7, 'votes' => null], $ratings['kinopoisk']);
        $this->assertSame(67, $ratings['metacritic']['value']);
        $this->assertSame(81, $ratings['rotten_tomatoes']['value']);
        $this->assertSame(8.437, $ratings['tmdb']['value']);
    }

    public function testARatingProviderWithoutAScoreIsNull(): void
    {
        $transport = (new FakeTransport())->queueJson(self::tvRatings());
        $client = $this->makeClient($transport);

        $ratings = $client->tv->ratings(1396);

        $this->assertSame('/3/tv/1396/ratings', self::pathOf($transport->last()['url']));
        $this->assertNull($ratings['metacritic']);
        $this->assertSame(9.5, $ratings['imdb']['value']);
        $this->assertSame(96, $ratings['rotten_tomatoes']['value']);
    }

    public function testRatingsOfATitleWithoutScoresAreAllNull(): void
    {
        $client = $this->makeClient((new FakeTransport())->queueJson(self::emptyRatings()));

        $ratings = $client->movies->ratings(1798888);

        foreach (['imdb', 'kinopoisk', 'metacritic', 'rotten_tomatoes', 'tmdb'] as $provider) {
            $this->assertNull($ratings[$provider]);
        }
    }

    public function testWikidataReturnsMoneyFieldsAndLinks(): void
    {
        $transport = (new FakeTransport())->queueJson(self::sequelWikidata());
        $client = $this->makeClient($transport);

        $wikidata = $client->movies->wikidata(604);

        $this->assertSame('/3/movie/604/wikidata', self::pathOf($transport->last()['url']));
        $this->assertSame('Q189600', $wikidata['wikidata_id']);
        $this->assertSame(['value' => 150000000, 'currency' => 'USD'], $wikidata['budget']);
        $this->assertSame(739412035, $wikidata['box_office']['value']);
        $this->assertTrue($wikidata['box_office']['worldwide']);
        $this->assertSame(603, $wikidata['follows']['tmdb_id']);
        $this->assertSame('movie', $wikidata['follows']['media_type']);
        $this->assertSame(605, $wikidata['followed_by']['tmdb_id']);
        $this->assertSame('The Matrix Reloaded', $wikidata['sitelinks']['en']);
        $this->assertArrayNotHasKey('tmdb_id', $wikidata['filming_locations'][0]);
    }

    public function testWikidataAwardsAndSources(): void
    {
        $client = $this->makeClient((new FakeTransport())->queueJson(self::awardedWikidata()));

        $wikidata = $client->movies->wikidata(155);

        $this->assertSame(2009, $wikidata['awards'][0]['year']);
        $this->assertSame('Academy Award for Best Cinematography', $wikidata['nominations'][1]['label']);
        $this->assertSame('Batman', $wikidata['based_on'][0]['label']);
        $this->assertNull($wikidata['based_on'][0]['author']);
        $this->assertSame([], $wikidata['original_broadcasters']);
    }

    public function testShowtimesWithoutACountryListCountries(): void
    {
        $transport = (new FakeTransport())->queueJson(self::showtimeCountries());
        $client = $this->makeClient($transport);

        $showtimes = $client->movies->showtimes(550);

        $request = $transport->last();
        $this->assertSame('/3/movie/550/showtimes', self::pathOf($request['url']));
        $this->assertSame([], self::queryOf($request['url']));
        $this->assertNull($showtimes['country']);
        $this->assertSame([], $showtimes['results']);
        $this->assertSame('RU', $showtimes['countries'][0]['country']);
        $this->assertSame(2, $showtimes['countries'][0]['venues']);
        $this->assertSame(7, $showtimes['countries'][0]['showtimes']);
        $this->assertSame('2026-10-21', $showtimes['countries'][0]['last_day']);
    }

    public function testShowtimesWithACountryReturnVenues(): void
    {
        $transport = (new FakeTransport())->queueJson(self::russianShowtimes());
        $client = $this->makeClient($transport);

        $showtimes = $client->movies->showtimes(550, 'RU');

        $request = $transport->last();
        $this->assertSame('/3/movie/550/showtimes', self::pathOf($request['url']));
        $this->assertSame(['country' => 'RU'], self::queryOf($request['url']));
        $this->assertSame('RU', $showtimes['country']);
        $venue = $showtimes['results'][0];
        $this->assertSame(1114, $venue['id']);
        $this->assertSame('mirage-ru', $venue['chain']['key']);
        $this->assertSame('Europe/Moscow', $venue['timezone']);
        $this->assertNull($venue['latitude']);
        $this->assertSame('2026-10-18T21:40', $venue['showtimes'][0]['start']);
        $this->assertSame(['2D'], $venue['showtimes'][0]['formats']);
        $this->assertFalse($venue['showtimes'][0]['sold_out']);
    }

    public function testShowtimesTakeTheRegionAliasThroughParams(): void
    {
        $transport = (new FakeTransport())->queueJson(self::russianShowtimes());
        $client = $this->makeClient($transport);

        $client->movies->showtimes(550, null, ['region' => 'RU']);

        $this->assertSame(['region' => 'RU'], self::queryOf($transport->last()['url']));
    }

    public function testTvShowtimesWithoutDataHaveNoCountries(): void
    {
        $transport = (new FakeTransport())->queueJson(['id' => 1396, 'country' => null, 'results' => [], 'countries' => []]);
        $client = $this->makeClient($transport);

        $showtimes = $client->tv->showtimes(1396);

        $this->assertSame('/3/tv/1396/showtimes', self::pathOf($transport->last()['url']));
        $this->assertSame([], $showtimes['countries']);
        $this->assertSame([], $showtimes['results']);
    }

    public function testAppendedExtensionSectionsKeepTheirShape(): void
    {
        $body = [
            'id' => 550,
            'moods' => self::appendedMoods(),
            'ratings' => self::appendedRatings(),
            'soundtracks' => ['results' => []],
            'showtimes' => self::showtimeCountries(),
        ];
        $transport = (new FakeTransport())->queueJson($body);
        $client = $this->makeClient($transport);

        $movie = $client->movies->details(550, ['moods', 'ratings', 'soundtracks', 'showtimes']);

        $this->assertSame(
            ['append_to_response' => 'moods,ratings,soundtracks,showtimes'],
            self::queryOf($transport->last()['url']),
        );
        $this->assertArrayNotHasKey('id', $movie['moods']);
        $this->assertArrayNotHasKey('id', $movie['ratings']);
        $this->assertArrayNotHasKey('id', $movie['soundtracks']);
        $this->assertSame(550, $movie['showtimes']['id']);
        $this->assertSame('estimated', $movie['moods']['source']);
        $this->assertSame(8.8, $movie['ratings']['imdb']['value']);
    }

    public function testFindByKinopoiskId(): void
    {
        $transport = (new FakeTransport())->queueJson(self::findResult());
        $client = $this->makeClient($transport);

        $found = $client->find('361', 'kinopoisk_id');

        $request = $transport->last();
        $this->assertSame('/3/find/361', self::pathOf($request['url']));
        $this->assertSame(['external_source' => 'kinopoisk_id'], self::queryOf($request['url']));
        $this->assertSame(
            ['movie_results', 'person_results', 'tv_results', 'tv_episode_results', 'tv_season_results'],
            array_keys($found),
        );
        $this->assertSame(550, $found['movie_results'][0]['id']);
        $this->assertSame('movie', $found['movie_results'][0]['media_type']);
    }

    public function testFindByWikidataIdReturnsPeople(): void
    {
        $transport = (new FakeTransport())->queueJson(self::findPersonResult());
        $client = $this->makeClient($transport);

        $found = $client->find('Q35332', 'wikidata_id');

        $request = $transport->last();
        $this->assertSame('/3/find/Q35332', self::pathOf($request['url']));
        $this->assertSame(['external_source' => 'wikidata_id'], self::queryOf($request['url']));
        $this->assertSame('person', $found['person_results'][0]['media_type']);
        $this->assertSame([], $found['person_results'][0]['known_for']);
        $this->assertSame([], $found['movie_results']);
    }

    public function testExternalIdsCarryKinopoiskId(): void
    {
        $transport = (new FakeTransport())->queueJson(self::movieExternalIds())->queueJson(self::tvExternalIds());
        $client = $this->makeClient($transport);

        $movie = $client->movies->externalIds(550);
        $tv = $client->tv->externalIds(1396);

        $this->assertSame('361', $movie['kinopoisk_id']);
        $this->assertSame('Q190050', $movie['wikidata_id']);
        $this->assertSame('404900', $tv['kinopoisk_id']);
        $this->assertSame(81189, $tv['tvdb_id']);
    }

    public function testAnUnknownTitleOnAnExtensionEndpointRaisesNotFound(): void
    {
        $body = ['success' => false, 'status_code' => 34, 'status_message' => 'The resource you requested could not be found.'];
        $client = $this->makeClient((new FakeTransport())->queueJson($body, 404));

        try {
            $client->tv->wikidata(999999999);
            $this->fail('Expected an AmodbException.');
        } catch (AmodbException $e) {
            $this->assertSame(404, $e->getStatus());
            $this->assertSame(34, $e->getStatusCode());
            $this->assertSame('The resource you requested could not be found.', $e->getStatusMessage());
            $this->assertSame($body, $e->getBody());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function measuredMoods(): array
    {
        return [
            'id' => 155,
            'source' => 'measured',
            'results' => [
                [
                    'name' => 'action',
                    'avg' => 3.9,
                    'values' => [3.6, 3.4, 4.2, 4.2, 4.8, 3],
                ],
                [
                    'name' => 'fear',
                    'avg' => 5.2,
                    'values' => [3.6, 6, 4.8, 6.4, 6.4, 3.8],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function estimatedTvMoods(): array
    {
        return [
            'id' => 1396,
            'source' => 'estimated',
            'results' => [
                [
                    'name' => 'action',
                    'avg' => 4,
                    'values' => [3, 4, 5, 4, 3],
                ],
                [
                    'name' => 'fear',
                    'avg' => 2,
                    'values' => [1, 2, 3, 2, 1],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyMoods(): array
    {
        return [
            'id' => 1798888,
            'source' => null,
            'results' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function appendedMoods(): array
    {
        return [
            'source' => 'estimated',
            'results' => [
                [
                    'name' => 'action',
                    'avg' => 4,
                    'values' => [3, 4, 5, 4, 3],
                ],
                [
                    'name' => 'fear',
                    'avg' => 7,
                    'values' => [6, 7, 8, 7, 6],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function musicbrainzSoundtrack(): array
    {
        return [
            'id' => 155,
            'results' => [
                [
                    'source' => 'musicbrainz',
                    'album' => 'The Dark Knight',
                    'artist' => 'Hans Zimmer & James Newton Howard',
                    'released' => '2008-07-15',
                    'url' => 'https://musicbrainz.org/release/814b2f7b-c27c-451e-abac-a316d2e6cea9',
                    'track_count' => 14,
                    'tracks' => [
                        [
                            'disc' => 1,
                            'number' => 1,
                            'title' => 'Why So Serious?',
                            'artist' => 'Hans Zimmer',
                            'duration_ms' => 554000,
                        ],
                        [
                            'disc' => 1,
                            'number' => 2,
                            'title' => 'I\'m Not a Hero',
                            'artist' => 'Hans Zimmer',
                            'duration_ms' => 395000,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function deezerSoundtrack(): array
    {
        return [
            'id' => 27205,
            'results' => [
                [
                    'source' => 'deezer',
                    'album' => 'Inception (Music from the Motion Picture)',
                    'artist' => 'Hans Zimmer',
                    'released' => '2010-07-09',
                    'url' => 'https://www.deezer.com/album/601778',
                    'track_count' => 12,
                    'tracks' => [
                        [
                            'disc' => 1,
                            'number' => 1,
                            'title' => 'Half Remembered Dream',
                            'artist' => 'Hans Zimmer',
                            'duration_ms' => 71000,
                        ],
                        [
                            'disc' => 1,
                            'number' => 2,
                            'title' => 'We Built Our Own World',
                            'artist' => 'Hans Zimmer',
                            'duration_ms' => 115000,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function variousArtistsSoundtrack(): array
    {
        return [
            'id' => 13,
            'results' => [
                [
                    'source' => 'musicbrainz',
                    'album' => 'Forrest Gump: The Soundtrack',
                    'artist' => null,
                    'released' => '1994-06-27',
                    'url' => 'https://musicbrainz.org/release/e4a08d83-945c-3eb8-ab87-099752e4d94c',
                    'track_count' => 32,
                    'tracks' => [
                        [
                            'disc' => 1,
                            'number' => 1,
                            'title' => 'Hound Dog',
                            'artist' => 'Elvis Presley',
                            'duration_ms' => 138493,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function movieRatings(): array
    {
        return [
            'id' => 550,
            'imdb' => [
                'value' => 8.8,
                'votes' => 2669989,
            ],
            'kinopoisk' => [
                'value' => 8.7,
                'votes' => null,
            ],
            'metacritic' => [
                'value' => 67,
                'votes' => null,
            ],
            'rotten_tomatoes' => [
                'value' => 81,
                'votes' => null,
            ],
            'tmdb' => [
                'value' => 8.437,
                'votes' => 33039,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function tvRatings(): array
    {
        return [
            'id' => 1396,
            'imdb' => [
                'value' => 9.5,
                'votes' => 2671962,
            ],
            'kinopoisk' => [
                'value' => 8.9,
                'votes' => null,
            ],
            'metacritic' => null,
            'rotten_tomatoes' => [
                'value' => 96,
                'votes' => null,
            ],
            'tmdb' => [
                'value' => 9,
                'votes' => 18863,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyRatings(): array
    {
        return [
            'id' => 1798888,
            'imdb' => null,
            'kinopoisk' => null,
            'metacritic' => null,
            'rotten_tomatoes' => null,
            'tmdb' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function appendedRatings(): array
    {
        return [
            'imdb' => [
                'value' => 8.8,
                'votes' => 2669989,
            ],
            'kinopoisk' => [
                'value' => 8.7,
                'votes' => null,
            ],
            'metacritic' => [
                'value' => 67,
                'votes' => null,
            ],
            'rotten_tomatoes' => [
                'value' => 81,
                'votes' => null,
            ],
            'tmdb' => [
                'value' => 8.437,
                'votes' => 33039,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function sequelWikidata(): array
    {
        return [
            'id' => 604,
            'wikidata_id' => 'Q189600',
            'budget' => [
                'value' => 150000000,
                'currency' => 'USD',
            ],
            'box_office' => [
                'value' => 739412035,
                'currency' => 'USD',
                'worldwide' => true,
            ],
            'follows' => [
                'wikidata_id' => 'Q83495',
                'label' => 'The Matrix',
                'tmdb_id' => 603,
                'media_type' => 'movie',
            ],
            'followed_by' => [
                'wikidata_id' => 'Q207536',
                'label' => 'The Matrix Revolutions',
                'tmdb_id' => 605,
                'media_type' => 'movie',
            ],
            'sitelinks' => [
                'en' => 'The Matrix Reloaded',
                'ru' => 'Матрица: Перезагрузка',
                'uk' => 'Матриця: Перезавантаження',
            ],
            'awards' => [],
            'nominations' => [],
            'based_on' => [],
            'filming_locations' => [
                [
                    'wikidata_id' => 'Q3130',
                    'label' => 'Sydney',
                ],
            ],
            'narrative_locations' => [],
            'original_broadcasters' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function awardedWikidata(): array
    {
        return [
            'id' => 155,
            'wikidata_id' => 'Q163872',
            'budget' => [
                'value' => 185000000,
                'currency' => 'USD',
            ],
            'box_office' => [
                'value' => 1006234167,
                'currency' => 'USD',
                'worldwide' => true,
            ],
            'follows' => [
                'wikidata_id' => 'Q166262',
                'label' => 'Batman Begins',
                'tmdb_id' => 272,
                'media_type' => 'movie',
            ],
            'followed_by' => [
                'wikidata_id' => 'Q189330',
                'label' => 'The Dark Knight Rises',
                'tmdb_id' => 49026,
                'media_type' => 'movie',
            ],
            'sitelinks' => [
                'en' => 'The Dark Knight',
                'ru' => 'Тёмный рыцарь (фильм)',
                'uk' => 'Темний лицар (фільм)',
            ],
            'awards' => [
                [
                    'wikidata_id' => 'Q106291',
                    'label' => 'Academy Award for Best Supporting Actor',
                    'year' => 2009,
                ],
                [
                    'wikidata_id' => 'Q488645',
                    'label' => 'Academy Award for Best Sound Editing',
                    'year' => 2009,
                ],
            ],
            'nominations' => [
                [
                    'wikidata_id' => 'Q106291',
                    'label' => 'Academy Award for Best Supporting Actor',
                    'year' => 2009,
                ],
                [
                    'wikidata_id' => 'Q131520',
                    'label' => 'Academy Award for Best Cinematography',
                    'year' => 2009,
                ],
            ],
            'based_on' => [
                [
                    'wikidata_id' => 'Q2695156',
                    'label' => 'Batman',
                    'author' => null,
                ],
            ],
            'filming_locations' => [
                [
                    'wikidata_id' => 'Q65',
                    'label' => 'Los Angeles',
                ],
                [
                    'wikidata_id' => 'Q1297',
                    'label' => 'Chicago',
                ],
            ],
            'narrative_locations' => [
                [
                    'wikidata_id' => 'Q732858',
                    'label' => 'Gotham City',
                ],
                [
                    'wikidata_id' => 'Q8646',
                    'label' => 'Hong Kong',
                ],
            ],
            'original_broadcasters' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function showtimeCountries(): array
    {
        return [
            'id' => 550,
            'country' => null,
            'results' => [],
            'countries' => [
                [
                    'country' => 'RU',
                    'name' => 'Russia',
                    'venues' => 2,
                    'cities' => 1,
                    'showtimes' => 7,
                    'first_day' => '2026-10-18',
                    'last_day' => '2026-10-21',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function russianShowtimes(): array
    {
        return [
            'id' => 550,
            'country' => 'RU',
            'results' => [
                [
                    'id' => 1114,
                    'name' => 'Мираж Синема НА БОЛЬШОМ',
                    'chain' => [
                        'key' => 'mirage-ru',
                        'name' => 'Мираж Синема',
                    ],
                    'city' => 'Санкт-Петербург',
                    'region' => null,
                    'address' => 'Большой пр. П.С., д. 35А',
                    'postal_code' => null,
                    'country' => 'RU',
                    'timezone' => 'Europe/Moscow',
                    'latitude' => null,
                    'longitude' => null,
                    'phone' => null,
                    'url' => 'https://www.mirage.ru/spb/cinema/1/',
                    'showtimes' => [
                        [
                            'date' => '2026-10-18',
                            'start' => '2026-10-18T21:40',
                            'end' => null,
                            'auditorium' => null,
                            'formats' => ['2D'],
                            'tags' => [],
                            'audio' => null,
                            'subtitles' => null,
                            'language' => null,
                            'price' => null,
                            'currency' => null,
                            'prices' => null,
                            'booking_url' => 'https://www.mirage.ru/ticket_new/aca7b114-d587-4fc5-9df6-28568e8fa9b3/',
                            'sold_out' => false,
                        ],
                    ],
                ],
            ],
            'countries' => [
                [
                    'country' => 'RU',
                    'name' => 'Russia',
                    'venues' => 2,
                    'cities' => 1,
                    'showtimes' => 7,
                    'first_day' => '2026-10-18',
                    'last_day' => '2026-10-21',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function findResult(): array
    {
        return [
            'movie_results' => [
                [
                    'adult' => false,
                    'backdrop_path' => '/c6OLXfKAk5BKeR6broC8pYiCquX.jpg',
                    'genre_ids' => [18],
                    'id' => 550,
                    'original_language' => 'en',
                    'original_title' => 'Fight Club',
                    'overview' => 'A ticking-time-bomb insomniac and a slippery soap salesma...',
                    'popularity' => 39.0054,
                    'poster_path' => '/jSziioSwPVrOy9Yow3XhWIBDjq1.jpg',
                    'release_date' => '1999-10-15',
                    'title' => 'Fight Club',
                    'video' => false,
                    'vote_average' => 8.437,
                    'vote_count' => 33039,
                    'media_type' => 'movie',
                ],
            ],
            'person_results' => [],
            'tv_results' => [],
            'tv_episode_results' => [],
            'tv_season_results' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function findPersonResult(): array
    {
        return [
            'movie_results' => [],
            'person_results' => [
                [
                    'adult' => false,
                    'gender' => 2,
                    'id' => 287,
                    'known_for_department' => 'Acting',
                    'name' => 'Brad Pitt',
                    'original_name' => 'Brad Pitt',
                    'popularity' => 13.5158,
                    'profile_path' => '/ajNaPmXVVMJFg9GWmu6MJzTaXdV.jpg',
                    'known_for' => [],
                    'media_type' => 'person',
                ],
            ],
            'tv_results' => [],
            'tv_episode_results' => [],
            'tv_season_results' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function movieExternalIds(): array
    {
        return [
            'id' => 550,
            'imdb_id' => 'tt0137523',
            'wikidata_id' => 'Q190050',
            'facebook_id' => 'FightClub',
            'instagram_id' => null,
            'twitter_id' => null,
            'kinopoisk_id' => '361',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function tvExternalIds(): array
    {
        return [
            'id' => 1396,
            'imdb_id' => 'tt0903747',
            'freebase_mid' => null,
            'freebase_id' => null,
            'tvdb_id' => 81189,
            'tvrage_id' => null,
            'wikidata_id' => 'Q1079',
            'facebook_id' => 'BreakingBad',
            'instagram_id' => 'breakingbad',
            'twitter_id' => 'BreakingBad',
            'kinopoisk_id' => '404900',
        ];
    }
}
