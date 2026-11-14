<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads\Pages;

use App\Filament\Resources\SupportThreads\SupportThreadResource;
use Filament\Resources\Pages\ManageRecords;

final class ManageSupportThreads extends ManageRecords
{
    protected static string $resource = SupportThreadResource::class;
}
