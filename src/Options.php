<?php

declare(strict_types=1);

namespace Amodb;

use Amodb\Http\TransportInterface;

/**
 * Validated client options.
 *
 * Pass an instance to the Client constructor, or pass a plain array with the same
 * names (see fromArray()).
 */
final class Options
{
    public const DEFAULT_BASE_URL = 'https://api.amodb.dev/3';
    public const DEFAULT_OMDB_URL = 'https://api.amodb.dev/omdb/';
    public const DEFAULT_IMAGE_BASE_URL = 'https://img.amodb.dev/t/p/';
    public const DEFAULT_TIMEOUT = 15.0;

    /** Send the key as `Authorization: Bearer KEY`. */
    public const AUTH_BEARER = 'bearer';

    /** Send the key as the `api_key` query parameter. */
    public const AUTH_QUERY = 'query';

    /** Hard cap on retries. A request is attempted at most MAX_RETRIES + 1 times. */
    public const MAX_RETRIES = 3;

    private const KNOWN = [
        'baseUrl',
        'omdbUrl',
        'imageBaseUrl',
        'language',
        'timeout',
        'maxRetries',
        'auth',
        'transport',
        'sleep',
    ];

    /** Base URL of the TMDB style API, without a trailing slash. */
    public readonly string $baseUrl;

    /** URL of the OMDb style endpoint. */
    public readonly string $omdbUrl;

    /** Base URL for image paths. */
    public readonly string $imageBaseUrl;

    /** Default language tag sent as `language`, or null to send none. */
    public readonly ?string $language;

    /** Seconds allowed for one HTTP request. */
    public readonly float $timeout;

    /** Retries after an HTTP 429, from 0 to 3. */
    public readonly int $maxRetries;

    /** Either AUTH_BEARER or AUTH_QUERY. */
    public readonly string $auth;

    /** Custom transport, or null for the curl transport. */
    public readonly ?TransportInterface $transport;

    /** Function that waits for the given number of seconds, or null for the real sleep. */
    public readonly ?\Closure $sleep;

    private readonly string $origin;

    /**
     * @param string                   $baseUrl      Base URL of the TMDB style API.
     * @param string                   $omdbUrl      URL of the OMDb style endpoint.
     * @param string                   $imageBaseUrl Base URL for image paths.
     * @param string|null              $language     Default language tag, for example "ru-RU".
     * @param float                    $timeout      Seconds per HTTP request.
     * @param int                      $maxRetries   Retries after HTTP 429, capped at 3.
     * @param string                   $auth         "bearer" or "query".
     * @param TransportInterface|null  $transport    Custom transport.
     * @param \Closure|null            $sleep        Called with float seconds before a retry.
     */
    public function __construct(
        string $baseUrl = self::DEFAULT_BASE_URL,
        string $omdbUrl = self::DEFAULT_OMDB_URL,
        string $imageBaseUrl = self::DEFAULT_IMAGE_BASE_URL,
        ?string $language = null,
        float $timeout = self::DEFAULT_TIMEOUT,
        int $maxRetries = self::MAX_RETRIES,
        string $auth = self::AUTH_BEARER,
        ?TransportInterface $transport = null,
        ?\Closure $sleep = null,
    ) {
        $baseUrl = rtrim(trim($baseUrl), '/');
        $parts = parse_url($baseUrl);
        if (
            !is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        ) {
            throw new \InvalidArgumentException('Option "baseUrl" must be an absolute http or https URL.');
        }

        $omdbUrl = trim($omdbUrl);
        if ($omdbUrl === '') {
            throw new \InvalidArgumentException('Option "omdbUrl" must not be empty.');
        }

        $imageBaseUrl = trim($imageBaseUrl);
        if ($imageBaseUrl === '') {
            throw new \InvalidArgumentException('Option "imageBaseUrl" must not be empty.');
        }

        if ($language !== null) {
            $language = trim($language);
            if ($language === '') {
                $language = null;
            }
        }

        if (!($timeout > 0)) {
            throw new \InvalidArgumentException('Option "timeout" must be greater than 0 seconds.');
        }

        $auth = strtolower(trim($auth));
        if ($auth !== self::AUTH_BEARER && $auth !== self::AUTH_QUERY) {
            throw new \InvalidArgumentException('Option "auth" must be "bearer" or "query".');
        }

        $this->baseUrl = $baseUrl;
        $this->origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $this->omdbUrl = $omdbUrl;
        $this->imageBaseUrl = $imageBaseUrl;
        $this->language = $language;
        $this->timeout = $timeout;
        $this->maxRetries = max(0, min(self::MAX_RETRIES, $maxRetries));
        $this->auth = $auth;
        $this->transport = $transport;
        $this->sleep = $sleep;
    }

    /**
     * Builds options from an array that uses the option names, for example
     * `['language' => 'ru-RU', 'timeout' => 10, 'auth' => 'query']`.
     *
     * Unknown names raise an InvalidArgumentException so that typos do not go unnoticed.
     * The `sleep` value may be any callable.
     *
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        foreach (array_keys($options) as $key) {
            if (!in_array($key, self::KNOWN, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unknown option "%s". Known options: %s.',
                    (string) $key,
                    implode(', ', self::KNOWN),
                ));
            }
        }

        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        if (!is_int($timeout) && !is_float($timeout)) {
            throw new \InvalidArgumentException('Option "timeout" must be a number of seconds.');
        }

        $maxRetries = $options['maxRetries'] ?? self::MAX_RETRIES;
        if (!is_int($maxRetries)) {
            throw new \InvalidArgumentException('Option "maxRetries" must be an integer.');
        }

        $transport = $options['transport'] ?? null;
        if ($transport !== null && !$transport instanceof TransportInterface) {
            throw new \InvalidArgumentException('Option "transport" must implement ' . TransportInterface::class . '.');
        }

        $sleep = $options['sleep'] ?? null;
        if ($sleep !== null) {
            if (!is_callable($sleep)) {
                throw new \InvalidArgumentException('Option "sleep" must be callable.');
            }
            $sleep = \Closure::fromCallable($sleep);
        }

        return new self(
            baseUrl: self::stringOption($options, 'baseUrl', self::DEFAULT_BASE_URL),
            omdbUrl: self::stringOption($options, 'omdbUrl', self::DEFAULT_OMDB_URL),
            imageBaseUrl: self::stringOption($options, 'imageBaseUrl', self::DEFAULT_IMAGE_BASE_URL),
            language: isset($options['language']) ? self::stringOption($options, 'language', '') : null,
            timeout: (float) $timeout,
            maxRetries: $maxRetries,
            auth: self::stringOption($options, 'auth', self::AUTH_BEARER),
            transport: $transport,
            sleep: $sleep,
        );
    }

    /**
     * The scheme, host and port of baseUrl, for example "https://api.amodb.dev".
     */
    public function origin(): string
    {
        return $this->origin;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function stringOption(array $options, string $key, string $default): string
    {
        if (!isset($options[$key])) {
            return $default;
        }
        $value = $options[$key];
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" must be a string.', $key));
        }

        return $value;
    }
}
