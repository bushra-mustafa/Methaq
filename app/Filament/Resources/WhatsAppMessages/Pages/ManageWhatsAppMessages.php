<?php

declare(strict_types=1);

namespace App\Filament\Resources\WhatsAppMessages\Pages;

use App\Filament\Resources\WhatsAppMessages\WhatsAppMessageResource;
use Filament\Resources\Pages\ManageRecords;

final class ManageWhatsAppMessages extends ManageRecords
{
    protected static string $resource = WhatsAppMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
