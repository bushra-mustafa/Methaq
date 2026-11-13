<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domains\Editor\Actions\ListFeaturedTemplatesAction;
use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(ListFeaturedTemplatesAction $listFeaturedTemplates): Response
    {
        return Inertia::render('Web/Home', [
            'showPrototype' => app()->isLocal(),
            'featuredTemplates' => $listFeaturedTemplates->execute(),
            'meta' => PageMeta::make('دعوات إلكترونية بظرف يُفتح', PageMeta::DEFAULT_DESCRIPTION, '/'),
        ]);
    }
}
