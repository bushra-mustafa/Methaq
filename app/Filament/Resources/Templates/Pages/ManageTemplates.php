<?php

declare(strict_types=1);

namespace App\Filament\Resources\Templates\Pages;

use App\Domains\Editor\Actions\ReorderTemplatesAction;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

final class ManageTemplates extends ManageRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Drag-and-drop order goes through the domain Action (authorised per template)
     * instead of Filament's direct column update.
     *
     * @param  array<int|string>  $order
     */
    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        app(ReorderTemplatesAction::class)->execute($user, array_values($order));
    }
}
