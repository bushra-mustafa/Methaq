<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Domains\Editor\Actions\ListAdminLibraryAction;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class LibraryController extends Controller
{
    public function __invoke(ListAdminLibraryAction $listLibrary): Response
    {
        return Inertia::render('App/Admin/Library/Index', $listLibrary->execute());
    }
}
