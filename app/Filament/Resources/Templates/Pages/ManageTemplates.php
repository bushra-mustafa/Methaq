<?php

declare(strict_types=1);

namespace App\Filament\Resources\Templates\Pages;

use App\Domains\Editor\Actions\CreateTemplateAction;
use App\Domains\Editor\Actions\ReorderTemplatesAction;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

final class ManageTemplates extends ManageRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('قالب جديد')
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading('قالب جديد من بطاقة بيضاء')
                ->modalDescription('يبدأ القالب مخفياً وبلا سعر. صمّميه في المحرر ثم سعّريه وأظهريه.')
                ->schema([
                    TextInput::make('name')->label('اسم القالب')->required()->maxLength(120),
                    Select::make('category')->label('التصنيف')->options(array_column(EventCategory::options(), 'label', 'value'))->required(),
                ])
                ->action(function (array $data): void {
                    $user = Filament::auth()->user();
                    abort_unless($user instanceof User, 403);
                    $template = app(CreateTemplateAction::class)->execute($user, (string) $data['name'], EventCategory::from((string) $data['category']));
                    $this->redirect(route('app.studio.templates.show', $template));
                }),
        ];
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
