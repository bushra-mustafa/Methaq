<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domains\Editor\Models\Template;
use App\Domains\Payments\Models\BankAccount;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use App\Filament\Resources\BankAccounts\BankAccountResource;
use App\Filament\Resources\Templates\TemplateResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** What still has to be set for money to flow: the receiving account, prices, featured templates, admin security. */
final class SetupChecklist extends TableWidget
{
    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->heading('جاهزية المنصة')
            ->records(fn (): array => $this->items())
            ->paginated(false)
            ->columns([
                IconColumn::make('ok')
                    ->label('')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedExclamationTriangle)
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('title')->label('البند')->weight('bold')->description(fn (array $record): string => $record['detail']),
                TextColumn::make('link')
                    ->label('')
                    ->state(fn (array $record): ?string => $record['url'] === null ? null : 'فتح')
                    ->url(fn (array $record): ?string => $record['url'])
                    ->icon(Heroicon::OutlinedArrowLeft)
                    ->color('primary'),
            ]);
    }

    /** @return array<string, array{ok: bool, title: string, detail: string, url: ?string}> */
    private function items(): array
    {
        $bank = (array) config('methaq.payment.bank_transfer', []);
        $bankReady = BankAccount::query()->where('is_active', true)->exists()
            || (filled($bank['bank_name'] ?? null) && filled($bank['account_name'] ?? null) && (filled($bank['account_number'] ?? null) || filled($bank['iban'] ?? null)));

        $active = Template::query()->where('is_active', true);
        $unpriced = (clone $active)->where('price_minor', 0)->count();
        $featured = (clone $active)->where('is_featured', true)->count();

        $admins = User::query()->where('role', UserRole::Admin->value)->where('status', UserStatus::Active->value);
        $withoutTwoFactor = (clone $admins)->whereNull('two_factor_confirmed_at')->count();
        $suspended = User::query()->where('status', UserStatus::Suspended->value)->count();

        return [
            'bank' => [
                'ok' => $bankReady,
                'title' => 'حساب استلام التحويلات',
                'detail' => $bankReady ? 'مضبوط ويظهر للعميلات في صفحة الدفع.' : 'غير مكتمل: أضيفي حساباً من «الحسابات البنكية»، وإلا تبقى صفحة الدفع مقفلة.',
                'url' => BankAccountResource::getUrl('index'),
            ],
            'prices' => [
                'ok' => $unpriced === 0,
                'title' => 'أسعار القوالب',
                'detail' => $unpriced === 0 ? 'كل القوالب الفعّالة لها سعر.' : "{$unpriced} قالب فعّال بلا سعر، ولا يمكن الدفع له.",
                'url' => TemplateResource::getUrl('index'),
            ],
            'featured' => [
                'ok' => $featured >= 1 && $featured <= 6,
                'title' => 'قوالب الصفحة الرئيسية',
                'detail' => $featured === 0 ? 'لا قالب مختار للرئيسية.' : ($featured > 6 ? "{$featured} قالب مختار، والرئيسية تعرض أول 6 فقط." : "{$featured} قالب مختار."),
                'url' => TemplateResource::getUrl('index'),
            ],
            'security' => [
                'ok' => $withoutTwoFactor === 0,
                'title' => 'أمان حسابات الإدارة',
                'detail' => $withoutTwoFactor === 0 ? 'كل المديرات فعّلن التحقق بخطوتين.' : "{$withoutTwoFactor} مديرة بلا تحقق بخطوتين.",
                'url' => UserResource::getUrl('index'),
            ],
            'suspended' => [
                'ok' => true,
                'title' => 'حسابات موقوفة',
                'detail' => $suspended === 0 ? 'لا حسابات موقوفة.' : "{$suspended} حساب موقوف حالياً.",
                'url' => $suspended > 0 ? UserResource::getUrl('index') : null,
            ],
        ];
    }
}
