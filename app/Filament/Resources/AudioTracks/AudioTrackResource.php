<?php

declare(strict_types=1);

namespace App\Filament\Resources\AudioTracks;

use App\Domains\Editor\Actions\UpdateLibraryItemStatusAction;
use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Enums\LibraryResource;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Users\Models\User;
use App\Filament\Resources\AudioTracks\Pages\ManageAudioTracks;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The music tracks customers can pick for their invitations. Only administrators upload or hide them;
 * a track a design already uses is never deleted, only hidden from new choices.
 */
final class AudioTrackResource extends Resource
{
    protected static ?string $model = TemplateAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static ?string $modelLabel = 'صوتية';

    protected static ?string $pluralModelLabel = 'الصوتيات';

    protected static ?string $navigationLabel = 'الصوتيات';

    protected static string|\UnitEnum|null $navigationGroup = 'التشغيل';

    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        return Gate::allows('create', TemplateAsset::class);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return Builder<TemplateAsset> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', AssetType::Audio->value);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([25, 50])
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->weight('bold')->description(fn (TemplateAsset $record): string => (string) $record->mime_type),
                TextColumn::make('size')
                    ->label('الحجم')
                    ->state(fn (TemplateAsset $record): string => is_array($record->metadata) && isset($record->metadata['sizeBytes']) ? number_format(((int) $record->metadata['sizeBytes']) / 1048576, 1).' م.ب' : '—'),
                IconColumn::make('is_active')->label('متاحة')->boolean(),
                TextColumn::make('created_at')->label('أُضيفت')->dateTime('d/m/Y')->timezone((string) config('methaq.invitation_timezone', 'Africa/Tripoli'))->sortable(),
            ])
            ->recordActions([self::listenAction(), self::statusAction()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAudioTracks::route('/')];
    }

    private static function listenAction(): Action
    {
        return Action::make('listen')
            ->label('استماع')
            ->icon(Heroicon::OutlinedPlay)
            ->color('gray')
            ->modalHeading(fn (TemplateAsset $record): string => $record->name)
            ->modalContent(fn (TemplateAsset $record): View => view('filament.audio.player', ['path' => $record->preview_path]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق');
    }

    private static function statusAction(): Action
    {
        return Action::make('status')
            ->label(fn (TemplateAsset $record): string => $record->is_active ? 'إخفاء' : 'إظهار')
            ->icon(fn (TemplateAsset $record) => $record->is_active ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
            ->color('gray')
            ->authorize('update')
            ->modalHeading(fn (TemplateAsset $record): string => ($record->is_active ? 'إخفاء من الخيارات · ' : 'إظهار في الخيارات · ').$record->name)
            ->modalDescription('التصاميم التي تستعمل هذه الصوتية تحتفظ بها، والإخفاء يمنعها من الظهور للاختيار الجديد فقط.')
            ->schema([Textarea::make('reason')->label('السبب')->required()->maxLength(300)])
            ->action(function (TemplateAsset $record, array $data): void {
                $actor = Filament::auth()->user();
                abort_unless($actor instanceof User, 403);
                app(UpdateLibraryItemStatusAction::class)->execute($actor, LibraryResource::Asset, (int) $record->getKey(), ! $record->is_active, (string) $data['reason']);
                Notification::make()->success()->title('تم تحديث الصوتية')->send();
            });
    }
}
