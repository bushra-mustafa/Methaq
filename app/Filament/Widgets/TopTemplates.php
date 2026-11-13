<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Editor\Models\Template;
use App\Domains\Payments\Services\LydAmount;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Which templates people start from and which ones actually earn. */
final class TopTemplates extends TableWidget
{
    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->heading('أكثر القوالب استعمالاً')
            ->query(fn () => Template::query()
                ->withCount('events')
                ->selectSub(
                    'select coalesce(sum(o.amount), 0) from orders o join events e on e.id = o.event_id'
                    ." where e.template_id = templates.id and o.status = 'completed'",
                    'revenue',
                )
                ->orderByDesc('events_count')
                ->orderBy('name')
                ->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('القالب')->weight('bold'),
                TextColumn::make('events_count')->label('مناسبات')->numeric()->icon(Heroicon::OutlinedEnvelopeOpen),
                TextColumn::make('revenue')->label('الإيراد')->state(fn (Template $record): string => LydAmount::format((int) $record->getAttribute('revenue'))),
            ]);
    }
}
