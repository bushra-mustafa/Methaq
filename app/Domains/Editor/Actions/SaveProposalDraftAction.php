<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\DTOs\ProposalDraftData;
use App\Domains\Editor\Exceptions\ProposalRevisionConflict;
use App\Domains\Editor\Models\DesignProposal;
use App\Domains\Editor\Services\ProposalDocumentValidator;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class SaveProposalDraftAction
{
    public function __construct(private readonly ProposalDocumentValidator $validator) {}

    /** @return array{document: array<string, mixed>, revision: int} */
    public function execute(int $ownerId, ProposalDraftData $data): array
    {
        return DB::transaction(function () use ($ownerId, $data): array {
            User::query()->whereKey($ownerId)->lockForUpdate()->firstOrFail();
            $draft = DesignProposal::query()->where('user_id', $ownerId)->where('template_key', ProposalDocumentValidator::TEMPLATE)->lockForUpdate()->first();
            if (($draft?->revision ?? 0) !== $data->expectedRevision) {
                throw new ProposalRevisionConflict(['document' => $draft?->document ?? $this->validator->initialDocument(), 'revision' => $draft?->revision ?? 0]);
            }
            $draft ??= new DesignProposal(['user_id' => $ownerId, 'template_key' => ProposalDocumentValidator::TEMPLATE]);
            $draft->fill(['document' => $data->document, 'revision' => $data->expectedRevision + 1])->save();

            return ['document' => $draft->document, 'revision' => $draft->revision];
        }, 3);
    }
}
