<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Client;
use PHPUnit\Framework\Attributes\DataProvider;

final class ResourcesTest extends ClientTestCase
{
    /**
     * Each entry is a call and the path it must request, below the /3 base.
     *
     * @return array<string, array{\Closure, string}>
     */
    public static function endpoints(): array
    {
        return [
            'movies.details' => [static fn (Client $c): mixed => $c->movies->details(550), '/movie/550'],
            'movies.credits' => [static fn (Client $c): mixed => $c->movies->credits(550), '/movie/550/credits'],
            'movies.images' => [static fn (Client $c): mixed => $c->movies->images(550), '/movie/550/images'],
            'movies.videos' => [static fn (Client $c): mixed => $c->movies->videos(550), '/movie/550/videos'],
            'movies.keywords' => [static fn (Client $c): mixed => $c->movies->keywords(550), '/movie/550/keywords'],
            'movies.externalIds' => [static fn (Client $c): mixed => $c->movies->externalIds(550), '/movie/550/external_ids'],
            'movies.recommendations' => [static fn (Client $c): mixed => $c->movies->recommendations(550), '/movie/550/recommendations'],
            'movies.similar' => [static fn (Client $c): mixed => $c->movies->similar(550), '/movie/550/similar'],
            'movies.reviews' => [static fn (Client $c): mixed => $c->movies->reviews(550), '/movie/550/reviews'],
            'movies.releaseDates' => [static fn (Client $c): mixed => $c->movies->releaseDates(550), '/movie/550/release_dates'],
            'movies.watchProviders' => [static fn (Client $c): mixed => $c->movies->watchProviders(550), '/movie/550/watch/providers'],
            'movies.translations' => [static fn (Client $c): mixed => $c->movies->translations(550), '/movie/550/translations'],
            'movies.alternativeTitles' => [static fn (Client $c): mixed => $c->movies->alternativeTitles(550), '/movie/550/alternative_titles'],
            'movies.lists' => [static fn (Client $c): mixed => $c->movies->lists(550), '/movie/550/lists'],
            'movies.popular' => [static fn (Client $c): mixed => $c->movies->popular(), '/movie/popular'],
            'movies.topRated' => [static fn (Client $c): mixed => $c->movies->topRated(), '/movie/top_rated'],
            'movies.upcoming' => [static fn (Client $c): mixed => $c->movies->upcoming(), '/movie/upcoming'],
            'movies.nowPlaying' => [static fn (Client $c): mixed => $c->movies->nowPlaying(), '/movie/now_playing'],
            'movies.latest' => [static fn (Client $c): mixed => $c->movies->latest(), '/movie/latest'],
            'movies.moods' => [static fn (Client $c): mixed => $c->movies->moods(550), '/movie/550/moods'],
            'movies.soundtracks' => [static fn (Client $c): mixed => $c->movies->soundtracks(550), '/movie/550/soundtracks'],
            'movies.ratings' => [static fn (Client $c): mixed => $c->movies->ratings(550), '/movie/550/ratings'],
            'movies.wikidata' => [static fn (Client $c): mixed => $c->movies->wikidata(550), '/movie/550/wikidata'],
            'movies.showtimes' => [static fn (Client $c): mixed => $c->movies->showtimes(550, 'US'), '/movie/550/showtimes'],
            'movies.showtimes without country' => [static fn (Client $c): mixed => $c->movies->showtimes(550), '/movie/550/showtimes'],

            'tv.details' => [static fn (Client $c): mixed => $c->tv->details(1396), '/tv/1396'],
            'tv.credits' => [static fn (Client $c): mixed => $c->tv->credits(1396), '/tv/1396/credits'],
            'tv.images' => [static fn (Client $c): mixed => $c->tv->images(1396), '/tv/1396/images'],
            'tv.videos' => [static fn (Client $c): mixed => $c->tv->videos(1396), '/tv/1396/videos'],
            'tv.keywords' => [static fn (Client $c): mixed => $c->tv->keywords(1396), '/tv/1396/keywords'],
            'tv.externalIds' => [static fn (Client $c): mixed => $c->tv->externalIds(1396), '/tv/1396/external_ids'],
            'tv.recommendations' => [static fn (Client $c): mixed => $c->tv->recommendations(1396), '/tv/1396/recommendations'],
            'tv.similar' => [static fn (Client $c): mixed => $c->tv->similar(1396), '/tv/1396/similar'],
            'tv.reviews' => [static fn (Client $c): mixed => $c->tv->reviews(1396), '/tv/1396/reviews'],
            'tv.contentRatings' => [static fn (Client $c): mixed => $c->tv->contentRatings(1396), '/tv/1396/content_ratings'],
            'tv.watchProviders' => [static fn (Client $c): mixed => $c->tv->watchProviders(1396), '/tv/1396/watch/providers'],
            'tv.translations' => [static fn (Client $c): mixed => $c->tv->translations(1396), '/tv/1396/translations'],
            'tv.alternativeTitles' => [static fn (Client $c): mixed => $c->tv->alternativeTitles(1396), '/tv/1396/alternative_titles'],
            'tv.lists' => [static fn (Client $c): mixed => $c->tv->lists(1396), '/tv/1396/lists'],
            'tv.episodeGroups' => [static fn (Client $c): mixed => $c->tv->episodeGroups(1396), '/tv/1396/episode_groups'],
            'tv.season' => [static fn (Client $c): mixed => $c->tv->season(1396, 2), '/tv/1396/season/2'],
            'tv.episode' => [static fn (Client $c): mixed => $c->tv->episode(1396, 2, 3), '/tv/1396/season/2/episode/3'],
            'tv.popular' => [static fn (Client $c): mixed => $c->tv->popular(), '/tv/popular'],
            'tv.topRated' => [static fn (Client $c): mixed => $c->tv->topRated(), '/tv/top_rated'],
            'tv.airingToday' => [static fn (Client $c): mixed => $c->tv->airingToday(), '/tv/airing_today'],
            'tv.onTheAir' => [static fn (Client $c): mixed => $c->tv->onTheAir(), '/tv/on_the_air'],
            'tv.latest' => [static fn (Client $c): mixed => $c->tv->latest(), '/tv/latest'],
            'tv.moods' => [static fn (Client $c): mixed => $c->tv->moods(1396), '/tv/1396/moods'],
            'tv.soundtracks' => [static fn (Client $c): mixed => $c->tv->soundtracks(1396), '/tv/1396/soundtracks'],
            'tv.ratings' => [static fn (Client $c): mixed => $c->tv->ratings(1396), '/tv/1396/ratings'],
            'tv.wikidata' => [static fn (Client $c): mixed => $c->tv->wikidata(1396), '/tv/1396/wikidata'],
            'tv.showtimes' => [static fn (Client $c): mixed => $c->tv->showtimes(1396), '/tv/1396/showtimes'],

            'people.details' => [static fn (Client $c): mixed => $c->people->details(287), '/person/287'],
            'people.combinedCredits' => [static fn (Client $c): mixed => $c->people->combinedCredits(287), '/person/287/combined_credits'],
            'people.movieCredits' => [static fn (Client $c): mixed => $c->people->movieCredits(287), '/person/287/movie_credits'],
            'people.tvCredits' => [static fn (Client $c): mixed => $c->people->tvCredits(287), '/person/287/tv_credits'],
            'people.images' => [static fn (Client $c): mixed => $c->people->images(287), '/person/287/images'],
            'people.externalIds' => [static fn (Client $c): mixed => $c->people->externalIds(287), '/person/287/external_ids'],
            'people.popular' => [static fn (Client $c): mixed => $c->people->popular(), '/person/popular'],

            'search.movie' => [static fn (Client $c): mixed => $c->search->movie('x'), '/search/movie'],
            'search.tv' => [static fn (Client $c): mixed => $c->search->tv('x'), '/search/tv'],
            'search.person' => [static fn (Client $c): mixed => $c->search->person('x'), '/search/person'],
            'search.multi' => [static fn (Client $c): mixed => $c->search->multi('x'), '/search/multi'],
            'search.collection' => [static fn (Client $c): mixed => $c->search->collection('x'), '/search/collection'],
            'search.company' => [static fn (Client $c): mixed => $c->search->company('x'), '/search/company'],
            'search.keyword' => [static fn (Client $c): mixed => $c->search->keyword('x'), '/search/keyword'],

            'discover.movie' => [static fn (Client $c): mixed => $c->discover->movie(), '/discover/movie'],
            'discover.tv' => [static fn (Client $c): mixed => $c->discover->tv(), '/discover/tv'],

            'trending.default' => [static fn (Client $c): mixed => $c->trending->get(), '/trending/all/day'],
            'trending.movie.week' => [static fn (Client $c): mixed => $c->trending->get('movie', 'week'), '/trending/movie/week'],
            'trending.tv.day' => [static fn (Client $c): mixed => $c->trending->get('tv', 'day'), '/trending/tv/day'],
            'trending.person.week' => [static fn (Client $c): mixed => $c->trending->get('person', 'week'), '/trending/person/week'],

            'genres.movies' => [static fn (Client $c): mixed => $c->genres->movies(), '/genre/movie/list'],
            'genres.tv' => [static fn (Client $c): mixed => $c->genres->tv(), '/genre/tv/list'],

            'collections.details' => [static fn (Client $c): mixed => $c->collections->details(10), '/collection/10'],
            'companies.details' => [static fn (Client $c): mixed => $c->companies->details(508), '/company/508'],
            'networks.details' => [static fn (Client $c): mixed => $c->networks->details(213), '/network/213'],
            'keywords.details' => [static fn (Client $c): mixed => $c->keywords->details(180547), '/keyword/180547'],

            'find' => [static fn (Client $c): mixed => $c->find('tt0137523', 'imdb_id'), '/find/tt0137523'],
            'find kinopoisk' => [static fn (Client $c): mixed => $c->find(361, 'kinopoisk_id'), '/find/361'],
            'configuration' => [static fn (Client $c): mixed => $c->configuration(), '/configuration'],

            'certifications.movie' => [static fn (Client $c): mixed => $c->certifications->movie(), '/certification/movie/list'],
            'certifications.tv' => [static fn (Client $c): mixed => $c->certifications->tv(), '/certification/tv/list'],

            'watchProviders.regions' => [static fn (Client $c): mixed => $c->watchProviders->regions(), '/watch/providers/regions'],
            'watchProviders.movie' => [static fn (Client $c): mixed => $c->watchProviders->movie(), '/watch/providers/movie'],
            'watchProviders.tv' => [static fn (Client $c): mixed => $c->watchProviders->tv(), '/watch/providers/tv'],
        ];
    }

    #[DataProvider('endpoints')]
    public function testEndpointPath(\Closure $call, string $expectedPath): void
    {
        $transport = (new FakeTransport())->queueJson(['ok' => true]);
        $client = $this->makeClient($transport);

        $result = $call($client);

        $this->assertSame(['ok' => true], $result);
        $this->assertSame(1, $transport->requestCount());
        $this->assertSame('/3' . $expectedPath, self::pathOf($transport->last()['url']));
    }

    /**
     * Each entry is a call and the query it must send.
     *
     * @return array<string, array{\Closure, array<string, string>}>
     */
    public static function queries(): array
    {
        return [
            'movies.popular page' => [static fn (Client $c): mixed => $c->movies->popular(3), ['page' => '3']],
            'movies.recommendations page' => [static fn (Client $c): mixed => $c->movies->recommendations(550, 2), ['page' => '2']],
            'tv.similar page' => [static fn (Client $c): mixed => $c->tv->similar(1396, 4), ['page' => '4']],
            'explicit page wins over params' => [static fn (Client $c): mixed => $c->movies->popular(2, ['page' => 9]), ['page' => '2']],
            'params page is kept' => [static fn (Client $c): mixed => $c->movies->popular(null, ['page' => 9]), ['page' => '9']],
            'movies.lists page' => [static fn (Client $c): mixed => $c->movies->lists(550, 2), ['page' => '2']],
            'tv.lists page' => [static fn (Client $c): mixed => $c->tv->lists(1396, 3), ['page' => '3']],
            'movies.showtimes country' => [static fn (Client $c): mixed => $c->movies->showtimes(550, 'US'), ['country' => 'US']],
            'movies.showtimes without country' => [static fn (Client $c): mixed => $c->movies->showtimes(550), []],
            'tv.showtimes country' => [static fn (Client $c): mixed => $c->tv->showtimes(1396, 'RU'), ['country' => 'RU']],
            'search.movie query and params' => [
                static fn (Client $c): mixed => $c->search->movie('fight club', ['year' => 1999, 'page' => 2]),
                ['query' => 'fight club', 'year' => '1999', 'page' => '2'],
            ],
            'search.tv query' => [static fn (Client $c): mixed => $c->search->tv('dark'), ['query' => 'dark']],
            'search language' => [static fn (Client $c): mixed => $c->search->movie('x', language: 'pt-BR'), ['query' => 'x', 'language' => 'pt-BR']],
            'discover list params' => [
                static fn (Client $c): mixed => $c->discover->movie(['with_genres' => [28, 12], 'sort_by' => 'popularity.desc']),
                ['with_genres' => '28,12', 'sort_by' => 'popularity.desc'],
            ],
            'trending params' => [static fn (Client $c): mixed => $c->trending->get('tv', 'week', ['page' => 2]), ['page' => '2']],
            'find source' => [static fn (Client $c): mixed => $c->find('Q42', 'wikidata_id'), ['external_source' => 'wikidata_id']],
            'find kinopoisk source' => [static fn (Client $c): mixed => $c->find('361', 'kinopoisk_id'), ['external_source' => 'kinopoisk_id']],
            'details append and language' => [
                static fn (Client $c): mixed => $c->movies->details(550, ['credits', 'images'], language: 'ru-RU'),
                ['append_to_response' => 'credits,images', 'language' => 'ru-RU'],
            ],
            'details append showtimes with country' => [
                static fn (Client $c): mixed => $c->movies->details(550, ['showtimes'], ['country' => 'RU']),
                ['append_to_response' => 'showtimes', 'country' => 'RU'],
            ],
            'people.details append' => [
                static fn (Client $c): mixed => $c->people->details(287, 'combined_credits'),
                ['append_to_response' => 'combined_credits'],
            ],
            'extra params pass through' => [
                static fn (Client $c): mixed => $c->movies->images(550, ['include_image_language' => 'en,null']),
                ['include_image_language' => 'en,null'],
            ],
        ];
    }

    /**
     * @param array<string, string> $expectedQuery
     */
    #[DataProvider('queries')]
    public function testEndpointQuery(\Closure $call, array $expectedQuery): void
    {
        $transport = (new FakeTransport())->queueJson(['ok' => true]);
        $client = $this->makeClient($transport);

        $call($client);

        $this->assertEquals($expectedQuery, self::queryOf($transport->last()['url']));
    }

    public function testTrendingRejectsAnUnknownMediaType(): void
    {
        $client = $this->makeClient(new FakeTransport());

        $this->expectException(\InvalidArgumentException::class);

        $client->trending->get('book', 'day');
    }

    public function testTrendingRejectsAnUnknownWindow(): void
    {
        $client = $this->makeClient(new FakeTransport());

        $this->expectException(\InvalidArgumentException::class);

        $client->trending->get('movie', 'month');
    }
}
