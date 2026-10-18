<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Trending endpoint: `$client->trending`.
 */
final class Trending extends AbstractResource
{
    private const MEDIA_TYPES = ['all', 'movie', 'tv', 'person'];

    private const WINDOWS = ['day', 'week'];

    /**
     * @param string               $mediaType One of all, movie, tv, person.
     * @param string               $window    One of day, week.
     * @param array<string, mixed> $params
     *
     * @throws \InvalidArgumentException When the media type or the window is not one of the allowed values.
     */
    public function get(string $mediaType = 'all', string $window = 'day', array $params = [], ?string $language = null): mixed
    {
        if (!in_array($mediaType, self::MEDIA_TYPES, true)) {
            throw new \InvalidArgumentException('mediaType must be one of: ' . implode(', ', self::MEDIA_TYPES) . '.');
        }
        if (!in_array($window, self::WINDOWS, true)) {
            throw new \InvalidArgumentException('window must be one of: ' . implode(', ', self::WINDOWS) . '.');
        }

        return $this->fetch('/trending/' . $mediaType . '/' . $window, $params, $language);
    }
}
