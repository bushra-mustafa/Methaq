<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreeOffers\Pages;

use App\Domains\Payments\Actions\SaveFreeOfferAction;
use App\Domains\Users\Models\User;
use App\Filament\Resources\FreeOffers\FreeOfferResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

final class ManageFreeOffers extends ManageRecords
{
    protected static string $resource = FreeOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('عرض مجاني جديد')
                ->using(function (array $data): Model {
                    $actor = Filament::auth()->user();
                    abort_unless($actor instanceof User, 403);

                    /** @var array{title: string, description?: ?string, template_id?: int|string|null, max_claims: int|string, starts_at: mixed, ends_at: mixed, is_active: bool} $data */
                    return app(SaveFreeOfferAction::class)->execute($actor, $data);
                }),
        ];
    }
}
