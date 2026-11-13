<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domains\Editor\Models\Template;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * «Use this template» for everyone: a signed-in owner goes straight to creating an invitation from
 * it; a visitor is sent to sign up and, once registered, lands on the same step with the template
 * still chosen (Fortify returns to the remembered address). Hidden or unknown templates are 404.
 */
final class StartFromTemplateController extends Controller
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $template = Template::query()->onSale()->where('slug', $slug)->firstOrFail();

        if ($request->user() !== null) {
            return redirect()->route('app.events.create', ['template' => $template->slug]);
        }

        return redirect()->guest(route('register'));
    }
}
