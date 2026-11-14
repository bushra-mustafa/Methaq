<?php

declare(strict_types=1);

namespace App\Filament\Resources\LibraryAssets\Pages;

use App\Domains\Editor\Actions\UploadLibraryAssetAction;
use App\Domains\Editor\Enums\AssetType;
use App\Domains\Users\Models\User;
use App\Filament\Resources\LibraryAssets\LibraryAssetResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class ManageLibraryAssets extends ManageRecords
{
    protected static string $resource = LibraryAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('رفع عنصر')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('رفع عنصر إلى المكتبة')
                ->modalDescription('يظهر العنصر فوراً لكل المصممات في المحرر. SVG يحتاج صورة معاينة PNG بالأبعاد نفسها.')
                ->modalSubmitActionLabel('رفع')
                ->schema([
                    Select::make('type')->label('النوع')->options([
                        AssetType::Frame->value => 'إطار',
                        AssetType::Decoration->value => 'زخرفة',
                        AssetType::Icon->value => 'أيقونة أو ختم',
                        AssetType::Background->value => 'خلفية',
                        AssetType::Font->value => 'خط',
                    ])->required()->live(),
                    TextInput::make('name')->label('الاسم الظاهر للمصممات')->required()->maxLength(120),
                    TextInput::make('fontFamily')->label('اسم عائلة الخط')->maxLength(60)
                        ->visible(fn (Get $get): bool => $get('type') === AssetType::Font->value)
                        ->required(fn (Get $get): bool => $get('type') === AssetType::Font->value),
                    FileUpload::make('file')
                        ->label('الملف')
                        ->required()
                        ->storeFiles(false)
                        ->maxSize(5 * 1024)
                        ->helperText(fn (Get $get): string => $get('type') === AssetType::Font->value ? 'خط WOFF2 أو TTF أو OTF حتى 3 ميغابايت.' : 'SVG أو PNG أو JPG أو WebP حتى 5 ميغابايت. يفضَّل PNG شفاف للزخارف.'),
                    FileUpload::make('preview')
                        ->label('صورة المعاينة PNG (مع SVG فقط)')
                        ->storeFiles(false)
                        ->maxSize(5 * 1024)
                        ->acceptedFileTypes(['image/png'])
                        ->visible(fn (Get $get): bool => $get('type') !== AssetType::Font->value),
                    Textarea::make('source')->label('المصدر أو الترخيص')->rows(2)->required()->maxLength(300)
                        ->helperText('مثال: تصميم ميثاق، أو رخصة تجارية من موقع كذا، أو بإذن صاحبه.'),
                    Checkbox::make('rights')->label('أؤكد أن لدينا حق استخدام هذا العنصر داخل دعوات العملاء.')->accepted()->required(),
                ])
                ->action(function (array $data): void {
                    $admin = Filament::auth()->user();
                    abort_unless($admin instanceof User, 403);
                    $file = $data['file'];
                    $preview = $data['preview'] ?? null;
                    abort_unless($file instanceof UploadedFile, 422);

                    try {
                        app(UploadLibraryAssetAction::class)->execute(
                            $admin,
                            AssetType::from((string) $data['type']),
                            (string) $data['name'],
                            $file,
                            $preview instanceof UploadedFile ? $preview : null,
                            (string) $data['source'],
                            (bool) $data['rights'],
                            isset($data['fontFamily']) ? (string) $data['fontFamily'] : null,
                        );
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title((string) collect($exception->errors())->flatten()->first())->send();

                        return;
                    }

                    Notification::make()->success()->title('رُفع العنصر وصار متاحاً في المحرر')->send();
                }),
        ];
    }
}
