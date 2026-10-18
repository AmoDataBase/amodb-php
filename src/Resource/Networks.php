<?php

declare(strict_types=1);

namespace Amodb\Resource;

/**
 * TV networks: `$client->networks`.
 */
final class Networks extends AbstractResource
{
    /**
     * @param array<string, mixed> $params
     */
    public function details(int|string $id, array $params = [], ?string $language = null): mixed
    {
        return $this->fetch('/network/' . self::seg($id), $params, $language);
    }
}
