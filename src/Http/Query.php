<?php

declare(strict_types=1);

namespace Amodb\Http;

/**
 * Builds query strings the way the API expects them.
 *
 * - null values are dropped.
 * - true and false become the text "true" and "false".
 * - lists are joined with commas, so ['credits', 'images'] becomes "credits,images".
 * - keys are sent exactly as given.
 * - keys and values are percent-encoded with rawurlencode (RFC 3986).
 */
final class Query
{
    /**
     * @param array<array-key, mixed> $params
     */
    public static function build(array $params): string
    {
        $pairs = [];
        foreach ($params as $key => $value) {
            $encoded = self::value($value);
            if ($encoded === null) {
                continue;
            }
            $pairs[] = rawurlencode((string) $key) . '=' . rawurlencode($encoded);
        }

        return implode('&', $pairs);
    }

    /**
     * Converts one parameter value to its text form, or null when it must be dropped.
     */
    private static function value(mixed $value): ?string
    {
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    throw new \InvalidArgumentException('Nested arrays are not supported in query parameters.');
                }
                $part = self::scalar($item);
                if ($part !== null) {
                    $parts[] = $part;
                }
            }

            return $parts === [] ? null : implode(',', $parts);
        }

        return self::scalar($value);
    }

    private static function scalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value) || is_string($value)) {
            return (string) $value;
        }
        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(sprintf('Unsupported query parameter type: %s.', get_debug_type($value)));
    }
}
