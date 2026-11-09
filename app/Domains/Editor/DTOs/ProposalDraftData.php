<?php

declare(strict_types=1);

namespace App\Domains\Editor\DTOs;

final readonly class ProposalDraftData
{
    /** @param array<string, mixed> $document */
    public function __construct(public array $document, public int $expectedRevision) {}
}
