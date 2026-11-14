<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactChannels\Pages;

use App\Domains\Users\Actions\SaveContactChannelAction;
use App\Domains\Users\Models\User;
use App\Filament\Resources\ContactChannels\ContactChannelResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageContactChannels extends ManageRecords
{
    protected static string $resource = ContactChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة وسيلة تواصل')
                ->using(function (array $data): Model {
                    $actor = Filament::auth()->user();
                    abort_unless($actor instanceof User, 403);

                    /** @var array{name: string, url: string, icon: string, is_active: bool, sort_order: int} $data */
                    return app(SaveContactChannelAction::class)->execute($actor, $data);
                }),
        ];
    }
}
