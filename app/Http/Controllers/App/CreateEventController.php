<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Events\Actions\ListEventCreationOptionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CreateEventPageRequest;
use Inertia\Inertia;
use Inertia\Response;

final class CreateEventController extends Controller
{
    public function __invoke(CreateEventPageRequest $request, ListEventCreationOptionsAction $listOptions): Response
    {
        return Inertia::render('App/Events/Create', $listOptions->execute($request->templateSlug()));
    }
}
