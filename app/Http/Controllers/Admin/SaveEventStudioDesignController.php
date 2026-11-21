<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Editor\Actions\SaveEventDesignAsAdministratorAction;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\SaveEventStudioDesignRequest;
use Illuminate\Http\JsonResponse;

final class SaveEventStudioDesignController extends Controller
{
    public function __invoke(
        SaveEventStudioDesignRequest $request,
        Event $event,
        DesignDocumentHydrator $hydrator,
        SaveEventDesignAsAdministratorAction $saveDesign,
    ): JsonResponse {
        $admin = $request->user();
        abort_unless($admin instanceof User, 401);

        return response()->json($saveDesign->execute($admin, $event, $request->toData($hydrator))->toArray());
    }
}
