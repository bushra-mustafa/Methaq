<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Editor\Actions\SaveTemplateDesignAction;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\SaveTemplateDesignRequest;
use Illuminate\Http\JsonResponse;

final class SaveTemplateDesignController extends Controller
{
    public function __invoke(
        SaveTemplateDesignRequest $request,
        Template $template,
        DesignDocumentHydrator $hydrator,
        SaveTemplateDesignAction $saveDesign,
    ): JsonResponse {
        $admin = $request->user();
        abort_unless($admin instanceof User, 401);

        return response()->json($saveDesign->execute($admin, $template, $request->toData($hydrator))->toArray());
    }
}
