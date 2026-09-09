<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use InvalidArgumentException;

final readonly class SaveDesignData
{
    public function __construct(
        public DesignDocumentData $document,
        public int $expectedRevision,
    ) {
        if ($expectedRevision < 1) {
            throw new InvalidArgumentException('Expected revision must be positive.');
        }
    }
}
