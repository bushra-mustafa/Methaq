<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domains\Editor\Actions\ListTemplateLibraryAction;
use App\Domains\Events\Enums\EventCategory;
use App\Http\Controllers\Controller;
use App\Http\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

/** A landing page per occasion: what Methaq offers for it and the templates priced for it. */
final class OccasionController extends Controller
{
    public function __invoke(string $slug, ListTemplateLibraryAction $listLibrary): Response
    {
        $category = EventCategory::fromSlug($slug);
        abort_if($category === null, 404);

        $pitch = $category->pitch();
        $templates = $listLibrary->execute($category)['templates'];

        return Inertia::render('Web/Occasion', [
            'occasion' => ['slug' => $category->slug(), 'label' => $category->label(), ...$pitch],
            'templates' => $templates,
            'occasions' => array_map(
                static fn (EventCategory $other): array => ['slug' => $other->slug(), 'label' => $other->label()],
                EventCategory::cases(),
            ),
            'meta' => PageMeta::make('دعوات '.$category->label().' إلكترونية', $pitch['description'], route('occasions.show', $category->slug(), false)),
        ]);
    }
}
