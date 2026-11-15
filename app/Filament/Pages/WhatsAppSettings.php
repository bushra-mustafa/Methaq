<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domains\Users\Actions\SaveCloudMsgSettingsAction;
use App\Domains\Users\Actions\TestCloudMsgConnectionAction;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Domains\Users\Services\WhatsAppFailures;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class WhatsAppSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'إعدادات واتساب';

    protected static ?string $title = 'إعدادات واتساب';

    protected static string|\UnitEnum|null $navigationGroup = 'النظام';

    protected static ?string $slug = 'whatsapp-settings';

    protected string $view = 'filament.pages.whatsapp-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('manage-whatsapp-settings');
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $this->form->fill(app(CloudMsgSettings::class)->formValues());
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('testConnection')->label('اختبار الاتصال وإرسال رسالة')->icon(Heroicon::OutlinedSignal)
            ->modalHeading('اختبار إعدادات واتساب المحفوظة')
            ->modalDescription('يفحص اتصال رقم الإرسال ويرسل رسالة واحدة إلى رقم استقبال الإشعارات المحفوظ، حتى لو الإرسال العادي مطفّي. احفظي أي تعديل قبل الاختبار. تُرسل رسالة اختبار واحدة في الدقيقة. قبول الرسالة لا يؤكد تسليمها؛ تأكدي من وصولها على الهاتف.')
            ->requiresConfirmation()
            ->fillForm(fn (): array => ['recipient' => CloudMsgSettings::get('admin_phone')])
            ->schema([TextInput::make('recipient')->label('رقم استقبال رسالة الاختبار')->readOnly()->required()->extraInputAttributes(['dir' => 'ltr'])])
            ->action(function (array $data): void {
                $actor = Filament::auth()->user();
                abort_unless($actor instanceof User, 403);
                $message = app(TestCloudMsgConnectionAction::class)->execute($actor, (string) $data['recipient']);
                if (in_array($message->status, ['queued', 'sent', 'acknowledged'], true)) {
                    Notification::make()->success()->title('الخدمة متصلة وقبلت رسالة الاختبار')->body('تأكدي من وصول الرسالة على هاتفك. الاختبار لا يثبت تشغيل المعالجة التلقائية.')->send();
                } else {
                    Notification::make()->danger()->title('لم تُقبل رسالة الاختبار بعد')->body(WhatsAppFailures::label($message->failure_code))->send();
                }
            })];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('بيانات الربط')->description('خذي القيم من حساب CloudMsg المرتبط برقم الإرسال.')->schema([
                TextInput::make('base_url')->label('رابط خدمة API')->url()->required()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('instance_id')->label('معرّف رقم الإرسال (Instance ID)')->placeholder('inst_…')->maxLength(128)->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('api_key')->label('مفتاح API')->password()->autocomplete('new-password')->maxLength(4096)
                    ->placeholder(fn (): string => CloudMsgSettings::get('api_key') ? 'محفوظ — اتركيه فارغاً للاحتفاظ به' : 'الصقي مفتاح الرقم هنا')
                    ->helperText('يُحفظ مشفّراً ولا يظهر بعد الحفظ. المفتاح هو القيمة نفسها، وليس رابطاً.'),
                TextInput::make('admin_phone')->label('رقم واتساب لاستقبال إشعارات الدفع')->tel()->placeholder('+21891XXXXXXX')->maxLength(16)->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('رقمك الذي يستقبل إشعار رفع القسيمة؛ رقم الإرسال تحدده جلسة CloudMsg.'),
            ])->columns(2),
            Section::make('استقبال الرسائل من واتساب')->schema([
                TextInput::make('webhook_url')->label('رابط استقبال الرسائل (Webhook)')->url()->maxLength(2048)
                    ->placeholder(rtrim((string) config('app.url'), '/').'/hooks/cloudmsg')->extraInputAttributes(['dir' => 'ltr'])
                    ->helperText('ضعي رابط موقعك العام HTTPS وينتهي بـ /hooks/cloudmsg، ثم أضيفيه في CloudMsg. عنوان localhost لا يستقبل رسائل المنصة.'),
                TextInput::make('webhook_secret')->label('سر توقيع Webhook')->password()->autocomplete('new-password')->maxLength(4096)
                    ->placeholder(fn (): string => CloudMsgSettings::get('webhook_secret') ? 'محفوظ — اتركيه فارغاً للاحتفاظ به' : 'الصقي السر الذي يرجعه CloudMsg')
                    ->helperText('يُحفظ مشفّراً. تركه فارغاً يحافظ على القيمة السابقة.'),
                Toggle::make('enabled')->label('تفعيل رسائل واتساب والنشر من المحادثة')
                    ->helperText('فعّليه بعد ربط الرقم وتسجيل Webhook وتشغيل معالجة الرسائل على الاستضافة.'),
            ]),
        ]);
    }

    public function save(): void
    {
        $actor = Filament::auth()->user();
        abort_unless($actor instanceof User && self::canAccess(), 403);
        try {
            $state = $this->form->getState();
            app(SaveCloudMsgSettingsAction::class)->execute($actor, $state);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $field): array => [str_starts_with($field, 'data.') ? $field : 'data.'.$field => $messages])->all());
        } finally {
            // Never return newly submitted secrets in a subsequent Livewire response.
            $this->data['api_key'] = '';
            $this->data['webhook_secret'] = '';
        }
        $this->form->fill(app(CloudMsgSettings::class)->formValues());
        Notification::make()->success()->title('تم حفظ إعدادات واتساب')->body('تُستخدم الإعدادات الجديدة في الطلبات ومهام الإرسال التالية.')->send();
    }
}
