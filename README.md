<!-- GitHub topics: amodb, tmdb, omdb, php, composer, packagist, movie-api, tv-api, api-client, sdk, psr-18 -->

# amodb-php

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

Every method returns the decoded JSON body as a PHP array. The fields are the same as in TMDB v3.

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

The array is joined with commas into `append_to_response=credits,images,ratings`. A plain string works too. Besides the TMDB sections, amodb accepts `moods`, `soundtracks`, `ratings`, `wikidata` and `showtimes`.

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
```

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
// Sources: imdb_id, tvdb_id, wikidata_id, kinopoisk_id
```

## Extras

```php
$amodb->movies->moods(550);
$amodb->movies->soundtracks(550);
$amodb->movies->ratings(550);
$amodb->movies->wikidata(550);
$amodb->movies->showtimes(550, 'US');
$amodb->tv->ratings(1396);
```

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

## License

MIT. See `LICENSE`.
