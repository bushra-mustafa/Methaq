<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Editor\Actions\SaveProposalDraftAction;
use App\Domains\Editor\Services\ProposalDocumentValidator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\SaveProposalDraftRequest;
use Illuminate\Http\JsonResponse;

final class SaveProposalDraftController extends Controller
{
    public function __invoke(SaveProposalDraftRequest $request, ProposalDocumentValidator $validator, SaveProposalDraftAction $save): JsonResponse
    {
        return response()->json($save->execute((int) $request->user()->getAuthIdentifier(), $request->toData($validator)));
    }
}
