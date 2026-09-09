<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Domains\Editor\Actions\SaveDesignAction;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\SaveDesignRequest;
use Illuminate\Http\JsonResponse;

final class SaveEventDesignController extends Controller
{
    public function __invoke(
        SaveDesignRequest $request,
        Event $event,
        DesignDocumentHydrator $hydrator,
        SaveDesignAction $saveDesign,
    ): JsonResponse {
        $owner = $request->user();
        abort_unless($owner instanceof User, 401);

        return response()->json($saveDesign->execute($owner, $event, $request->toData($hydrator))->toArray());
    }
}
