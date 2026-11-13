<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domains\Editor\Actions\ListTemplateLibraryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\BrowseTemplateLibraryRequest;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

final class TemplateLibraryController extends Controller
{
    public function __invoke(BrowseTemplateLibraryRequest $request, ListTemplateLibraryAction $listLibrary): Response
    {
        return Inertia::render('Web/Templates', [
            ...$listLibrary->execute($request->category()),
            'selectedCategory' => $request->category()?->value,
            'meta' => PageMeta::make('مكتبة القوالب', 'اختاري قالب دعوة لزفافك أو حنّتك أو عقد قرانك أو تخرجك أو افتتاح مشروعك أو استقبال مولودك، وعدّلي كل تفاصيله.', '/templates'),
        ]);
    }
}
