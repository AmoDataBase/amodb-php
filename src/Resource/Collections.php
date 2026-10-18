<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * Movie collections: `$client->collections`.
 */
final class Collections extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function details(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/collection/' . self::seg($id), $params, $language);
    }
}
