<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Editor\Actions\UpdateTemplateThumbnailAction;
use App\Domains\Editor\Models\Template;
use App\Domains\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\StoreTemplateThumbnailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

final class StoreTemplateThumbnailController extends Controller
{
    public function __invoke(StoreTemplateThumbnailRequest $request, Template $template, UpdateTemplateThumbnailAction $update): JsonResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 401);
        $preview = $request->file('preview');
        abort_unless($preview instanceof UploadedFile, 422);
        $saved = $update->execute($admin, $template, $preview);

        return response()->json(['thumbnailUrl' => '/'.ltrim($saved->thumbnail_path, '/')]);
    }
}
