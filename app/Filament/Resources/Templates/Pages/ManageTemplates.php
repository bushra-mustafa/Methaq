<?php

declare(strict_types=1);

namespace App\Filament\Resources\Templates\Pages;

use App\Domains\Editor\Actions\CreateTemplateAction;
use App\Domains\Editor\Actions\ReorderTemplatesAction;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Payments\Actions\UpdateBlankCardPriceAction;
use App\Domains\Payments\Services\LydAmount;
use App\Domains\Payments\Services\PricingSettings;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

final class ManageTemplates extends ManageRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('blankPrice')
                ->label('سعر البطاقة الفارغة')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->modalHeading('سعر التصميم من بطاقة فارغة')
                ->modalDescription('يدفعه من يبدأ تصميمه دون اختيار قالب. اكتبي 0 لإيقاف البطاقة الفارغة وإخفائها عن العميلات.')
                ->fillForm(fn (): array => ['price' => app(PricingSettings::class)->blankCardPriceMinor() / LydAmount::MINOR_UNITS_PER_DINAR])
                ->schema([
                    TextInput::make('price')->label('السعر بالدينار الليبي')->numeric()->minValue(0)->maxValue(100000)->step(0.5)->required(),
                ])
                ->action(function (array $data): void {
                    $user = Filament::auth()->user();
                    abort_unless($user instanceof User, 403);
                    app(UpdateBlankCardPriceAction::class)->execute($user, (int) round(((float) $data['price']) * LydAmount::MINOR_UNITS_PER_DINAR));
                    Notification::make()->success()->title('تم حفظ سعر البطاقة الفارغة')->send();
                }),
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
