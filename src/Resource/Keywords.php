<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Keywords: `$client->keywords`.
 */
final class Keywords extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function details(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/keyword/' . self::seg($id), $params, $language);
    }
}
