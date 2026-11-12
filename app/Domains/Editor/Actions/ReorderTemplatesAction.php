<?php

declare(strict_types=1);

namespace App\Domains\Editor\Actions;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Stores the administrator's drag-and-drop order (1, 2, 3 …) used by the home page and library. */
final class ReorderTemplatesAction
{
    /** @param list<int|string> $orderedIds */
    public function execute(User $actor, array $orderedIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $orderedIds)));

        DB::transaction(function () use ($actor, $ids): void {
            $templates = Template::query()->whereKey($ids)->lockForUpdate()->get()->keyBy('id');

            foreach ($ids as $position => $id) {
                $template = $templates->get($id);
                if (! $template instanceof Template) {
                    continue;
                }
                Gate::forUser($actor)->authorize('update', $template);
                $template->forceFill(['sort_order' => $position + 1])->save();
            }
        });
    }
}
