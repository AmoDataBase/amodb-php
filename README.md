<p align="center">
  <a href="https://amodb.dev">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/amodb-dev/.github/main/assets/lockup-dark.png">
      <img src="https://raw.githubusercontent.com/amodb-dev/.github/main/assets/lockup-light.png" alt="amodb" width="260">
    </picture>
  </a>
</p>

<h1 align="center">amodb-php</h1>

<p align="center">PHP client for the amodb movie and TV metadata API.</p>

<p align="center">
  <a href="https://github.com/amodb-dev/amodb-php/actions/workflows/ci.yml"><img alt="CI" src="https://github.com/amodb-dev/amodb-php/actions/workflows/ci.yml/badge.svg"></a>
  <a href="https://packagist.org/packages/amodb/amodb-php"><img alt="Packagist version" src="https://img.shields.io/packagist/v/amodb/amodb-php"></a>
  <a href="https://github.com/amodb-dev/amodb-php/blob/main/LICENSE"><img alt="License" src="https://img.shields.io/github/license/amodb-dev/amodb-php"></a>
</p>

PHP client for the amodb API. amodb serves movie and TV metadata through a TMDB v3 compatible API and an OMDb compatible endpoint.

Existing TMDB clients work by changing the base URL to `https://api.amodb.dev/3`. This package is a ready made client for the same API.

amodb is a paid API. Get a key at https://amodb.dev/pricing.

## Install

```sh
composer require amodb/amodb-php
```

It needs PHP 8.1 or newer with the `json` and `curl` extensions.

## Quick start

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Amodb\Client;

$amodb = new Client((string) getenv('AMODB_API_KEY'));

$hits = $amodb->search->movie('fight club');
$movie = $amodb->movies->details($hits['results'][0]['id']);

echo $movie['title'], ' ', substr($movie['release_date'], 0, 4), "\n";
echo $amodb->imageUrl($movie['poster_path'], 'w342'), "\n";
```

Every method returns the decoded JSON body as a PHP array. The fields are the same as in TMDB v3. The amodb extension methods document their reply shape in the `@return` tag, so editors and static analysers can check the keys.

## Authentication

By default the key is sent as `Authorization: Bearer KEY`. To send it as the `api_key` query parameter, use the `auth` option:

```php
$amodb = new Client($key, ['auth' => 'query']);
```

Keys are 32 lowercase hex characters. Keep them in environment variables, never in source control. An empty key raises `InvalidArgumentException` when you create the client. The client hides the key from `var_dump` and `print_r`. The curl transport never puts the request URL in an error message.

## Search

```php
$movies = $amodb->search->movie('alien', ['year' => 1979, 'page' => 1]);
$shows = $amodb->search->tv('dark');
$people = $amodb->search->person('sigourney weaver');
$mixed = $amodb->search->multi('matrix');
```

`collection`, `company` and `keyword` work the same way. The second argument is an array of extra query parameters.

## Details with append to response

```php
$movie = $amodb->movies->details(550, ['credits', 'images', 'ratings']);

echo count($movie['credits']['cast']), " cast members\n";
```

The array is joined with commas into `append_to_response=credits,images,ratings`. A plain string works too. The API takes up to 20 names and ignores unknown names without an error.

- Movies: `credits`, `images`, `videos`, `keywords`, `translations`, `external_ids`, `similar`, `recommendations`, `reviews`, `release_dates`, `watch/providers`, `alternative_titles`, `lists`, `moods`, `soundtracks`, `ratings`, `wikidata`, `showtimes`.
- TV shows: the same names, with `content_ratings` and `episode_groups` in place of `release_dates`.
- People: `combined_credits`, `movie_credits`, `tv_credits`, `external_ids`, `images`.

The appended `moods`, `ratings` and `soundtracks` sections have no `id` key. `wikidata` and `showtimes` keep theirs. To get the venues of one country with `showtimes`, pass the country in `$params`:

```php
$movie = $amodb->movies->details(550, ['showtimes'], ['country' => 'RU']);
```

More calls:

```php
$amodb->movies->credits(550);
$amodb->movies->recommendations(550, page: 2);
$amodb->tv->season(1396, 1);
$amodb->tv->episode(1396, 1, 1);
$amodb->people->combinedCredits(287);
$amodb->discover->movie(['with_genres' => [28, 12], 'sort_by' => 'popularity.desc']);
$amodb->trending->get('movie', 'week');
$amodb->genres->movies();
$amodb->movies->latest();
$amodb->tv->episodeGroups(1396);
```

List methods take `$page` from 1 to 500. `movies->lists()` and `tv->lists()` are paged too.

Resources on the client: `movies`, `tv`, `people`, `search`, `discover`, `trending`, `genres`, `collections`, `companies`, `networks`, `keywords`, `certifications`, `watchProviders` and `omdb`. Every method takes an optional `$params` array and an optional `$language`.

## Language

```php
// Default for every call
$amodb = new Client($key, ['language' => 'ru-RU']);

// One call
$movie = $amodb->movies->details(550, language: 'fr-FR');
```

The API serves 14 base languages: en ru uk be zh pt bg he cs ro fr pl it es. Any other tag falls back to `en`.

## Images

```php
$url = $amodb->imageUrl($movie['poster_path'], 'w500');
// https://img.amodb.dev/t/p/w500/abc123.jpg
```

Sizes are `w92`, `w154`, `w185`, `w300`, `w342`, `w500`, `w780`, `w1280`, `h632` and `original`. `imageUrl` returns `null` for a null or empty path. Image URLs need no key.

## OMDb

```php
$film = $amodb->omdb->byId('tt0137523', plot: 'full');
$film = $amodb->omdb->byTitle('Fight Club', year: 1999);
$page = $amodb->omdb->search('fight', type: 'movie', page: 2);

echo $film['Title'], ' ', $film['imdbRating'], "\n";
```

Bodies keep the OMDb field names. The key is sent as `apikey`. A body with `"Response": "False"` raises `AmodbException` with status 200 and the OMDb `Error` text as the message.

## Find by external id

```php
$found = $amodb->find('tt0137523', 'imdb_id');
echo $found['movie_results'][0]['title'], "\n"; // Fight Club

$found = $amodb->find('361', 'kinopoisk_id');
$found = $amodb->find('Q35332', 'wikidata_id');
echo $found['person_results'][0]['name'], "\n"; // Brad Pitt
```

These sources return results: `imdb_id` (`tt...` for titles, `nm...` for people), `tvdb_id`, `facebook_id`, `twitter_id`, `instagram_id`, `wikidata_id` and `kinopoisk_id`. `freebase_mid`, `freebase_id`, `tvrage_id`, `tiktok_id` and `youtube_id` are accepted and return empty lists. A missing or unknown source answers HTTP 400 with `status_code` 22.

The reply has `movie_results`, `person_results`, `tv_results`, `tv_episode_results` and `tv_season_results`. The last two are always empty. Every item has a `media_type`.

## Extras

Methods for the amodb extension endpoints exist on both `movies` and `tv`.

### Moods

```php
$moods = $amodb->movies->moods(155);

echo $moods['source'], "\n"; // measured
echo $moods['results'][0]['name'], ' ', $moods['results'][0]['avg'], "\n"; // action 3.9
```

`source` is `measured` for viewer data, `estimated` when the meters come from genres (whole numbers), or `null` with an empty `results` list when the title has no data. Each result has `name`, `avg` and `values` (up to 24 readings) on a 0 to 10 scale. The names are action, fear, humor, pace, profanity, romance, sadness, sex and violence. A title may have fewer than nine.

### Soundtracks

```php
$albums = $amodb->movies->soundtracks(155)['results'];

echo $albums[0]['album'], ' ', $albums[0]['track_count'], " tracks\n"; // The Dark Knight 14 tracks
echo $albums[0]['tracks'][0]['title'], "\n"; // Why So Serious?
```

`results` is empty when no album is known. An album has `source` (`musicbrainz` or `deezer`), `album`, `artist` (can be `null`), `released`, `url`, `track_count` and `tracks`. A track has `disc`, `number`, `title`, `artist` and `duration_ms`.

### Ratings

```php
$ratings = $amodb->movies->ratings(550);

echo $ratings['imdb']['value'], ' from ', $ratings['imdb']['votes'], " votes\n"; // 8.8 from 2669989 votes
```

The providers are `imdb`, `kinopoisk`, `metacritic`, `rotten_tomatoes` and `tmdb`. Each is `{value, votes}`, or `null` when the score is missing. `votes` can be `null`. imdb, kinopoisk and tmdb are out of 10, metacritic and rotten_tomatoes out of 100.

### Wikidata

```php
$wikidata = $amodb->movies->wikidata(604);

echo $wikidata['budget']['value'], ' ', $wikidata['budget']['currency'], "\n"; // 150000000 USD
echo $wikidata['follows']['label'], ' ', $wikidata['follows']['tmdb_id'], "\n"; // The Matrix 603
```

`budget` and `box_office` are `{value, currency}` or `null` (`box_office` adds `worldwide`). `follows` and `followed_by` are links or `null`. `sitelinks` maps a language code to a Wikipedia page title. `awards`, `nominations`, `based_on`, `filming_locations`, `narrative_locations` and `original_broadcasters` are lists. A link has `wikidata_id` and `label`, plus `tmdb_id` and `media_type` when the item is a title in the catalog.

### Showtimes

```php
// Which countries have showtimes
$countries = $amodb->movies->showtimes(550);
echo $countries['countries'][0]['country'], ' ', $countries['countries'][0]['venues'], " venues\n";

// Venues and showtimes for one country
$venues = $amodb->movies->showtimes(550, 'RU');
echo $venues['results'][0]['name'], ' ', $venues['results'][0]['showtimes'][0]['start'], "\n";
```

The country code is optional. Without it, `results` is empty and `countries` holds the counts per country: `venues`, `cities`, `showtimes`, `first_day` and `last_day`. With it, `results` lists the venues, and each venue has a `showtimes` list. `start` is the local time at the venue. Only days from today on are listed.

## Calls that are not offered

These TMDB paths answer 404 with `status_code` 34, so the client has no method for them: `aggregate_credits`, `changes`, `person/{id}/translations`, `person/{id}/tagged_images`, `credit/{id}`, `review/{id}`, `tv/episode_group/{id}`, images, translations and alternative names of collections, companies and networks, and `configuration/timezones`. Everything under `account`, `authentication`, `guest_session` and `list` answers 405 with `status_code` 37, and so does any `rating` or `account_states` path.

Some served calls return little: `reviews` is empty for most titles, and season and episode `videos` and `translations` are empty. The `include_adult` parameter is ignored, because adult titles and people are never served.

## Other calls

```php
$config = $amodb->configuration();
$plans = $amodb->plans();
$data = $amodb->request('/movie/550/credits', ['language' => 'en-US']);
```

`request()` is the escape hatch for any path under the base URL. It uses the same auth, language, retries and error handling.

## Error handling

```php
use Amodb\Exception\AmodbException;
use Amodb\Exception\NetworkException;
use Amodb\Exception\RateLimitException;
use Amodb\Exception\TimeoutException;

try {
    $movie = $amodb->movies->details(550);
} catch (RateLimitException $e) {
    echo 'Rate limited, retry in ', $e->getRetryAfter() ?? 'a few', " seconds\n";
} catch (TimeoutException | NetworkException $e) {
    echo 'No response: ', $e->getMessage(), "\n";
} catch (AmodbException $e) {
    echo $e->getStatus(), ' ', $e->getStatusCode(), ' ', $e->getStatusMessage(), "\n";
}
```

| Class | Raised when |
|---|---|
| `AmodbException` | The API answers with an error status, the body is not JSON, or OMDb answers `Response: False`. Base class of the others. |
| `RateLimitException` | HTTP 429 after the last retry. |
| `TimeoutException` | No response within the timeout. |
| `NetworkException` | The connection failed before a response arrived. |

| Method | Returns |
|---|---|
| `getStatus()` | HTTP status, or 0 when no response arrived. |
| `getStatusCode()` | The `status_code` of the error body, or `null`. |
| `getStatusMessage()` | The `status_message`, or a short text such as `HTTP 502`. |
| `getBody()` | The parsed JSON body, or the raw text when it was not JSON. |
| `getRetryAfter()` | On `RateLimitException`: seconds from `Retry-After`, or `null`. |

`Exception::getCode()` is final in PHP, so the TMDB code is in `getStatusCode()`.

API errors use the TMDB body shape:

| HTTP | status_code | Meaning |
|---|---|---|
| 401 | 7 | Invalid API key |
| 401 | 10 | Suspended API key |
| 402 | 36 | Plan expired |
| 404 | 34 | Resource not found |
| 405 | 37 | Method not offered by amodb |
| 429 | 25 | Over the rate limit |
| 500 | 11 | Internal error |
| 504 | 24 | Backend timed out |

## Retries and timeouts

- Only HTTP 429 is retried. Timeouts and network errors are raised at once.
- The client waits for `Retry-After` (seconds or an HTTP date, clamped to 0 to 60 seconds). Without the header it waits 1, 2, then 4 seconds.
- It retries at most 3 times, so a request is sent at most 4 times. Then it raises `RateLimitException`.
- `timeout` is 15 seconds per request by default.

```php
$amodb = new Client($key, ['timeout' => 5, 'maxRetries' => 1]);
```

## Options

Pass an array as the second argument, or an `Amodb\Options` object.

| Option | Default | Meaning |
|---|---|---|
| `baseUrl` | `https://api.amodb.dev/3` | Base URL of the TMDB style API |
| `omdbUrl` | `https://api.amodb.dev/omdb/` | OMDb endpoint |
| `imageBaseUrl` | `https://img.amodb.dev/t/p/` | Base URL used by `imageUrl()` |
| `language` | none | Default `language` tag |
| `timeout` | `15` | Seconds per request |
| `maxRetries` | `3` | Retries after HTTP 429, from 0 to 3 |
| `auth` | `bearer` | `bearer` or `query` |
| `transport` | curl | Object that implements `Amodb\Http\TransportInterface` |
| `sleep` | real sleep | Callable that takes float seconds, useful in tests |

Unknown option names raise `InvalidArgumentException`.

## Custom PSR-18 client

The default transport uses ext-curl. To use your own PSR-18 client, install any PSR-18 client and PSR-17 request factory, then wrap them:

```php
use Amodb\Client;
use Amodb\Http\Psr18Transport;

// $psr18 implements Psr\Http\Client\ClientInterface
// $factory implements Psr\Http\Message\RequestFactoryInterface
$amodb = new Client($key, [
    'transport' => new Psr18Transport($psr18, $factory),
]);
```

The package only suggests `psr/http-client` and `psr/http-factory`, and nothing loads `Psr18Transport` unless you use it. PSR-18 has no per request timeout, so the `timeout` option does not apply here. Set the timeout on your own client.

You can also write a transport: implement `TransportInterface::send()` and return a `TransportResponse`.

## Plans and rate limits

Plans differ only in requests per minute: lite 20, pro 40, proplus 120. Every plan gets all data. Live names and prices are at https://api.amodb.dev/plans, and `$amodb->plans()` returns the same JSON. The API sends `X-RateLimit-Limit`, `X-RateLimit-Remaining` and `X-RateLimit-Reset` on every keyed call.

## Development

```sh
composer install
composer test
```

The test suite was written for PHPUnit 10 on PHP 8.1 or newer. It uses a fake transport, so it needs no network and no API key.

## Links

- Website: https://amodb.dev
- Docs: https://amodb.dev/docs
- Pricing: https://amodb.dev/pricing
- Status: https://amodb.dev/status
- Issues: https://github.com/amodb-dev/amodb-php/issues
- Changelog: https://github.com/amodb-dev/amodb-php/blob/main/CHANGELOG.md
- Security: https://github.com/amodb-dev/amodb-php/blob/main/SECURITY.md
- Organization: https://github.com/amodb-dev

## License

MIT. See `LICENSE`.
