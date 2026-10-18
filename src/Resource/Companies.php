<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Production companies: `$client->companies`.
 */
final class Companies extends AbstractResource
{
    /**
     * The `description` is always empty and `parent_company` is always null.
     *
     * @param array<string, mixed> $params
     */
    public function details(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/company/' . self::seg($id), $params, $language);
    }
}
