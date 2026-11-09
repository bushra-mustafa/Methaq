<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Editor\Actions\GetIvoryProposalAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class IvoryProposalController extends Controller
{
    public function __invoke(Request $request, GetIvoryProposalAction $proposal): Response
    {
        return Inertia::render('App/Proposals/IvoryWedding', $proposal->execute((int) $request->user()->getAuthIdentifier()));
    }
}
