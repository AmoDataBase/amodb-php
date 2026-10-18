<?php

declare(strict_types=1);

namespace Amodb;

use Amodb\Exception\AmodbException;
use Amodb\Exception\RateLimitException;
use Amodb\Http\CurlTransport;
use Amodb\Http\Query;
use Amodb\Http\TransportInterface;
use Amodb\Http\TransportResponse;
use Amodb\Resource\Certifications;
use Amodb\Resource\Collections;
use Amodb\Resource\Companies;
use Amodb\Resource\Discover;
use Amodb\Resource\Genres;
use Amodb\Resource\Keywords;
use Amodb\Resource\Movies;
use Amodb\Resource\Networks;
use Amodb\Resource\Omdb;
use Amodb\Resource\People;
use Amodb\Resource\Search;
use Amodb\Resource\Trending;
use Amodb\Resource\Tv;
use Amodb\Resource\WatchProviders;

/**
 * Client for the amodb API, which is compatible with TMDB v3 and OMDb.
 *
 *     $amodb = new Client('your-key', ['language' => 'en-US']);
 *     $movie = $amodb->movies->details(550, ['credits']);
 *
 * Every method returns the decoded JSON body as a PHP array. Every failure raises a
 * subclass of \Amodb\Exception\AmodbException.
 */
class Client
{
    public const VERSION = '0.1.0';

    public const USER_AGENT = 'amodb-php/' . self::VERSION;

    /** Longest wait before a retry, in seconds. */
    private const MAX_WAIT = 60;

    public readonly Movies $movies;
    public readonly Tv $tv;
    public readonly People $people;
    public readonly Search $search;
    public readonly Discover $discover;
    public readonly Trending $trending;
    public readonly Genres $genres;
    public readonly Collections $collections;
    public readonly Companies $companies;
    public readonly Networks $networks;
    public readonly Keywords $keywords;
    public readonly Certifications $certifications;
    public readonly WatchProviders $watchProviders;
    public readonly Omdb $omdb;

    private readonly string $apiKey;
    private readonly Options $options;
    private readonly TransportInterface $transport;
    private readonly \Closure $sleeper;

    /**
     * @param string                       $apiKey  Your amodb API key.
     * @param array<string, mixed>|Options $options Options array or an Options object.
     *
     * @throws \InvalidArgumentException When the key is empty or an option is invalid.
     */
    public function __construct(#[\SensitiveParameter] string $apiKey, array|Options $options = [])
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new \InvalidArgumentException('An API key is required. Get one at https://amodb.dev/pricing');
        }

        $this->apiKey = $apiKey;
        $this->options = $options instanceof Options ? $options : Options::fromArray($options);
        $this->transport = $this->options->transport ?? new CurlTransport();
        $this->sleeper = $this->options->sleep ?? static function (float $seconds): void {
            if ($seconds > 0) {
                usleep((int) round($seconds * 1_000_000));
            }
        };

        $this->movies = new Movies($this);
        $this->tv = new Tv($this);
        $this->people = new People($this);
        $this->search = new Search($this);
        $this->discover = new Discover($this);
        $this->trending = new Trending($this);
        $this->genres = new Genres($this);
        $this->collections = new Collections($this);
        $this->companies = new Companies($this);
        $this->networks = new Networks($this);
        $this->keywords = new Keywords($this);
        $this->certifications = new Certifications($this);
        $this->watchProviders = new WatchProviders($this);
        $this->omdb = new Omdb($this);
    }

    /**
     * Sends a GET request to `baseUrl + $path` and returns the decoded JSON.
     *
     * Use it for any endpoint that has no method yet. Auth, language, retries and
     * error handling work the same as for the resource methods.
     *
     * @param string               $path     Path below the base URL, for example "/movie/550".
     * @param array<string, mixed> $params   Query parameters. null values are dropped.
     * @param string|null          $language Language tag that overrides the default language.
     *
     * @throws AmodbException
     */
    public function request(string $path, array $params = [], ?string $language = null): mixed
    {
        if ($language !== null && $language !== '') {
            $params['language'] = $language;
        } elseif (!isset($params['language']) && $this->options->language !== null) {
            $params['language'] = $this->options->language;
        }

        if ($this->options->auth === Options::AUTH_QUERY) {
            $params['api_key'] = $this->apiKey;
        }

        $url = self::buildUrl($this->options->baseUrl, '/' . ltrim($path, '/'), $params);

        return $this->execute($url, $this->headers(true));
    }

    /**
     * Finds movies, shows and people by an external id.
     *
     * These sources resolve: imdb_id (tt... for titles, nm... for people), tvdb_id, facebook_id,
     * twitter_id, instagram_id, wikidata_id (Q... ids) and kinopoisk_id. The other sources are
     * accepted and return empty lists. A missing or unknown source answers HTTP 400.
     * Each result carries `media_type`. The episode and season lists are always empty.
     *
     * @param int|string           $externalId     The external id, for example "tt0137523".
     * @param string               $externalSource One of imdb_id, freebase_mid, freebase_id, tvdb_id, tvrage_id,
     *                                             facebook_id, twitter_id, instagram_id, tiktok_id, youtube_id,
     *                                             wikidata_id, kinopoisk_id.
     * @param array<string, mixed> $params         Extra query parameters.
     *
     * @return array{movie_results: list<array<string, mixed>>, person_results: list<array<string, mixed>>, tv_results: list<array<string, mixed>>, tv_episode_results: list<array<string, mixed>>, tv_season_results: list<array<string, mixed>>}
     *
     * @throws AmodbException
     */
    public function find(int|string $externalId, string $externalSource, array $params = [], ?string $language = null): mixed
    {
        $params['external_source'] = $externalSource;

        return $this->request('/find/' . rawurlencode((string) $externalId), $params, $language);
    }

    /**
     * Returns the image configuration (base URLs and size lists).
     *
     * @param array<string, mixed> $params
     *
     * @throws AmodbException
     */
    public function configuration(array $params = []): mixed
    {
        return $this->request('/configuration', $params);
    }

    /**
     * Returns the public plans list from the API origin. No key is sent.
     *
     * @throws AmodbException
     */
    public function plans(): mixed
    {
        return $this->execute($this->options->origin() . '/plans', $this->headers(false));
    }

    /**
     * Builds an image URL. Returns null when the path is null or empty.
     *
     * @param string|null $path Image path such as "/abc.jpg". A missing leading slash is added.
     * @param string      $size One of w92, w154, w185, w300, w342, w500, w780, w1280, h632, original.
     */
    public function imageUrl(?string $path, string $size = 'w500'): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return rtrim($this->options->imageBaseUrl, '/') . '/' . trim($size, '/') . $path;
    }

    /**
     * Sends a request to the OMDb style endpoint. The key is always sent as `apikey`.
     *
     * An HTTP 200 body with `"Response": "False"` raises an AmodbException with status 200,
     * no status code and the OMDb `Error` text as the status message.
     *
     * @internal Use the methods of $client->omdb instead.
     *
     * @param array<string, mixed> $params
     *
     * @throws AmodbException
     */
    public function omdbRequest(array $params = []): mixed
    {
        $params = ['apikey' => $this->apiKey] + $params;
        $params['r'] = 'json';

        $data = $this->execute(self::buildUrl($this->options->omdbUrl, '', $params), $this->headers(false));

        if (is_array($data) && array_key_exists('Response', $data) && self::isFalse($data['Response'])) {
            $message = isset($data['Error']) && is_string($data['Error']) && $data['Error'] !== ''
                ? $data['Error']
                : 'The OMDb request failed.';

            throw new AmodbException($message, 200, null, $message, $data);
        }

        return $data;
    }

    /**
     * Keeps the key out of var_dump() and print_r() output.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'apiKey' => '[redacted]',
            'baseUrl' => $this->options->baseUrl,
            'auth' => $this->options->auth,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function headers(bool $withAuth): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => self::USER_AGENT,
        ];
        if ($withAuth && $this->options->auth === Options::AUTH_BEARER) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function buildUrl(string $base, string $path, array $params): string
    {
        $url = $base . $path;
        $query = Query::build($params);
        if ($query !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $query;
        }

        return $url;
    }

    /**
     * Sends the request, retries on HTTP 429 and maps the result to JSON or an exception.
     *
     * @param array<string, string> $headers
     */
    private function execute(string $url, array $headers): mixed
    {
        $attempt = 0;
        while (true) {
            $response = $this->transport->send('GET', $url, $headers, $this->options->timeout);

            if ($response->status === 429 && $attempt < $this->options->maxRetries) {
                ($this->sleeper)(self::retryDelay($response, $attempt));
                $attempt++;

                continue;
            }

            return self::decode($response);
        }
    }

    /**
     * Seconds to wait before retry number $attempt + 1.
     * Uses Retry-After when present, else 1, 2, 4 seconds. Always between 0 and 60.
     */
    private static function retryDelay(TransportResponse $response, int $attempt): float
    {
        $seconds = self::parseRetryAfter($response->header('retry-after')) ?? (2 ** $attempt);

        return (float) max(0, min(self::MAX_WAIT, $seconds));
    }

    /**
     * Reads a Retry-After value in seconds or as an HTTP date. Returns null when it is
     * missing or cannot be read.
     */
    private static function parseRetryAfter(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) max(0.0, min(86400.0, ceil((float) $value)));
        }
        $time = strtotime($value);
        if ($time === false) {
            return null;
        }

        return max(0, $time - time());
    }

    private static function decode(TransportResponse $response): mixed
    {
        $status = $response->status;
        if ($status < 200 || $status >= 300) {
            throw self::errorFor($response);
        }

        try {
            return json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $message = 'The response body was not valid JSON.';

            throw new AmodbException($message, $status, null, $message, $response->body, $e);
        }
    }

    private static function errorFor(TransportResponse $response): AmodbException
    {
        $status = $response->status;
        $raw = $response->body;
        $decoded = null;
        $isJson = false;

        if (trim($raw) !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $isJson = true;
            } catch (\JsonException) {
                $isJson = false;
            }
        }

        $body = $isJson ? $decoded : $raw;
        $statusCode = null;
        $statusMessage = null;

        if (is_array($decoded)) {
            if (isset($decoded['status_code']) && is_numeric($decoded['status_code'])) {
                $statusCode = (int) $decoded['status_code'];
            }
            foreach (['status_message', 'Error'] as $field) {
                if (isset($decoded[$field]) && is_string($decoded[$field]) && $decoded[$field] !== '') {
                    $statusMessage = $decoded[$field];
                    break;
                }
            }
        }

        $statusMessage ??= 'HTTP ' . $status;

        if ($status === 429) {
            return new RateLimitException(
                $statusMessage,
                $status,
                $statusCode,
                $statusMessage,
                $body,
                self::parseRetryAfter($response->header('retry-after')),
            );
        }

        return new AmodbException($statusMessage, $status, $statusCode, $statusMessage, $body);
    }

    private static function isFalse(mixed $value): bool
    {
        return $value === false || (is_string($value) && strtolower($value) === 'false');
    }
}
