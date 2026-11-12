<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Editor\Actions\UpdateTemplateFeaturedAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\UpdateTemplateFeaturedRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateTemplateFeaturedController extends Controller
{
    public function __invoke(UpdateTemplateFeaturedRequest $request, UpdateTemplateFeaturedAction $updateFeatured): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $updateFeatured->execute($actor, $request->templateId(), $request->isFeatured(), $request->reason());

        return back()->with('status', 'template-featured-updated');
    }
}
