<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use App\Domains\Editor\Contracts\DesignSchema;
use InvalidArgumentException;

final readonly class AssetReferenceData
{
    public function __construct(
        public string $assetId,
        public int $version,
    ) {
        if ($assetId === '' || strlen($assetId) > 20 || ! ctype_digit($assetId) || $assetId[0] === '0') {
            throw new InvalidArgumentException('Asset ID must be a positive database identifier serialized as a string.');
        }

        if ($version < 1 || $version > DesignSchema::MAXIMUM_ASSET_VERSION) {
            throw new InvalidArgumentException('Asset version is outside the supported range.');
        }
    }

    /** @return array{assetId: string, version: int} */
    public function toArray(): array
    {
        return ['assetId' => $this->assetId, 'version' => $this->version];
    }
}
