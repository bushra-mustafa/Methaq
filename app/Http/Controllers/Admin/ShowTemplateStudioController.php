<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Editor\Actions\GetTemplateEditorAction;
use App\Domains\Editor\Models\Template;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowTemplateStudioController extends Controller
{
    public function __invoke(Request $request, Template $template, GetTemplateEditorAction $getEditor): Response
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 401);

        return Inertia::render('App/Editor', $getEditor->execute($admin, $template));
    }
}
