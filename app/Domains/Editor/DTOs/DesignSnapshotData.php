<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

use InvalidArgumentException;

final readonly class DesignSnapshotData
{
    public function __construct(
        public DesignDocumentData $document,
        public int $revision,
    ) {
        if ($revision < 1) {
            throw new InvalidArgumentException('Snapshot revision must be positive.');
        }
    }

    /** @return array{document: array<string, mixed>, revision: int} */
    public function toArray(): array
    {
        return ['document' => $this->document->toArray(), 'revision' => $this->revision];
    }
}
