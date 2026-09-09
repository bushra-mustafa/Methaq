<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Admin;

use App\Domains\Editor\Actions\UpdateLibraryItemStatusAction;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\UpdateLibraryItemStatusRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateLibraryItemStatusController extends Controller
{
    public function __invoke(UpdateLibraryItemStatusRequest $request, UpdateLibraryItemStatusAction $updateStatus): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $updateStatus->execute(
            $actor,
            $request->resource(),
            $request->itemId(),
            $request->isActive(),
            $request->reason(),
        );

        return back()->with('status', 'library-status-updated');
    }
}
