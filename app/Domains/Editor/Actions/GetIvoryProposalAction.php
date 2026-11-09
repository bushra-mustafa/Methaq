<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\DesignProposal;
use App\Domains\Editor\Services\ProposalDocumentValidator;

final class GetIvoryProposalAction
{
    public function __construct(private readonly ProposalDocumentValidator $validator) {}

    /** @return array{document: array<string, mixed>, revision: int} */
    public function execute(int $ownerId): array
    {
        $draft = DesignProposal::query()->where('user_id', $ownerId)->where('template_key', ProposalDocumentValidator::TEMPLATE)->first();

        return ['document' => $draft?->document ?? $this->validator->initialDocument(), 'revision' => $draft?->revision ?? 0];
    }
}
