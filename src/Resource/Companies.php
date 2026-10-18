<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Production companies: `$client->companies`.
 */
final class Companies extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function details(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/company/' . self::seg($id), $params, $language);
    }
}
