<?php

declare(strict_types=1);

namespace App\Filament\Resources\AudioTracks\Pages;

use App\Domains\Editor\Actions\UploadAudioAssetAction;
use App\Domains\Users\Models\User;
use App\Filament\Resources\AudioTracks\AudioTrackResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class ManageAudioTracks extends ManageRecords
{
    protected static string $resource = AudioTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('رفع صوتية')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('رفع صوتية جديدة')
                ->modalDescription('تظهر الصوتية فوراً في خيارات الموسيقى لكل المصممات. صيغ MP3 وM4A وWAV وOGG حتى 10 ميغابايت.')
                ->modalSubmitActionLabel('رفع')
                ->schema([
                    TextInput::make('name')->label('اسم الصوتية')->required()->maxLength(120),
                    FileUpload::make('file')
                        ->label('الملف')
                        ->required()
                        ->storeFiles(false)
                        ->maxSize(10 * 1024)
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/wav', 'audio/x-wav', 'audio/ogg']),
                    Textarea::make('source')->label('المصدر أو الترخيص')->rows(2)->required()->maxLength(300)
                        ->helperText('مثال: تأليف ميثاق، أو رخصة تجارية من موقع كذا، أو بإذن صاحبه.'),
                    Checkbox::make('rights')->label('أؤكد أن لدينا حق استخدام هذه الصوتية داخل دعوات العملاء.')->accepted()->required(),
                ])
                ->action(function (array $data): void {
                    $admin = Filament::auth()->user();
                    abort_unless($admin instanceof User, 403);
                    $file = $data['file'];
                    abort_unless($file instanceof UploadedFile, 422);

                    try {
                        app(UploadAudioAssetAction::class)->execute($admin, (string) $data['name'], $file, (string) $data['source'], (bool) $data['rights']);
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title((string) collect($exception->errors())->flatten()->first())->send();

                        return;
                    }

                    Notification::make()->success()->title('رُفعت الصوتية وصارت متاحة في المحرر')->send();
                }),
        ];
    }
}
