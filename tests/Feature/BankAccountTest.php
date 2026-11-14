<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\DeleteBankAccountAction;
use App\Domains\Payments\Actions\SaveBankAccountAction;
use App\Domains\Payments\Models\BankAccount;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;
use App\Filament\Resources\BankAccounts\Pages\ManageBankAccounts;
use App\Filament\Widgets\SetupChecklist;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Bank account tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
        config(['methaq.payment.bank_transfer' => ['bank_name' => 'مصرف الخادم', 'account_name' => 'من الإعدادات', 'account_number' => '9999999999', 'iban' => null]]);
    }

    public function test_the_payment_page_shows_the_active_accounts_an_administrator_added_in_order_and_not_the_server_setting(): void
    {
        $admin = $this->administrator();
        $save = app(SaveBankAccountAction::class);
        $save->execute($admin, ['bank_name' => 'الثاني', 'account_name' => 'ميثاق', 'account_number' => '2222222222', 'is_active' => true, 'sort_order' => 2]);
        $save->execute($admin, ['bank_name' => 'الأول', 'account_name' => 'ميثاق', 'account_number' => null, 'iban' => 'ly83002048000020100120361', 'is_active' => true, 'sort_order' => 1]);
        $save->execute($admin, ['bank_name' => 'مخفي', 'account_name' => 'ميثاق', 'account_number' => '3333333333', 'is_active' => false, 'sort_order' => 0]);
        $event = Event::factory()->create(['template_id' => Template::factory()->create(['price_minor' => 10_000])->getKey()]);

        $this->actingAs($event->user)->withoutVite()->get("/app/events/{$event->getKey()}/payment")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('payment.bankAccounts', 2)
                ->where('payment.bankAccounts.0.bankName', 'الأول')
                ->where('payment.bankAccounts.0.accountNumber', null)
                ->where('payment.bankAccounts.0.iban', 'LY83002048000020100120361')
                ->where('payment.bankAccounts.1.accountNumber', '2222222222')
                ->where('payment.canSubmit', true));
    }

    public function test_with_no_stored_account_the_server_setting_is_used_and_with_neither_the_page_is_closed(): void
    {
        $this->assertSame('9999999999', app(ManualPaymentEligibility::class)->bankAccounts()[0]['accountNumber']);

        config(['methaq.payment.bank_transfer.account_number' => '']);
        $this->assertSame([], app(ManualPaymentEligibility::class)->bankAccounts());
    }

    public function test_an_account_needs_a_valid_number_or_iban_and_every_change_is_audited_with_a_masked_number(): void
    {
        $admin = $this->administrator();
        $save = app(SaveBankAccountAction::class);
        $base = ['bank_name' => 'مصرف', 'account_name' => 'ميثاق', 'is_active' => true, 'sort_order' => 0];

        foreach ([[], ['account_number' => 'abc123'], ['account_number' => '12'], ['iban' => 'not an iban!'], ['account_number' => '1234567890', 'bank_name' => ' ']] as $bad) {
            try {
                $save->execute($admin, [...$base, ...$bad]);
                $this->fail('An invalid bank account was saved.');
            } catch (ValidationException) {
                // expected
            }
        }
        $this->assertSame(0, BankAccount::query()->count());

        $account = $save->execute($admin, [...$base, 'account_number' => '0012 3456 7890']);
        $log = AuditLog::query()->where('action', 'bank_account.saved')->where('subject_id', $account->getKey())->firstOrFail();
        $this->assertSame('7890', $log->metadata['last4']);
        $this->assertStringNotContainsString('0012', json_encode($log->metadata, JSON_THROW_ON_ERROR));

        $save->execute($admin, [...$base, 'account_number' => '0012 3456 7890', 'is_active' => false], (int) $account->getKey());
        $this->assertFalse($account->fresh()->is_active);
        $this->assertSame(2, AuditLog::query()->where('action', 'bank_account.saved')->count());

        app(DeleteBankAccountAction::class)->execute($admin, (int) $account->getKey());
        $this->assertDatabaseMissing('bank_accounts', ['id' => $account->getKey()]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'bank_account.deleted', 'subject_id' => $account->getKey()]);
    }

    public function test_customers_cannot_change_bank_accounts_or_open_the_page(): void
    {
        $customer = User::factory()->create();
        $account = BankAccount::factory()->create();

        $this->actingAs($customer)->get('/admin/bank-accounts')->assertForbidden();

        foreach ([
            fn () => app(SaveBankAccountAction::class)->execute($customer, ['bank_name' => 'x', 'account_name' => 'y', 'account_number' => '1234567890', 'is_active' => true, 'sort_order' => 0]),
            fn () => app(SaveBankAccountAction::class)->execute($customer, ['bank_name' => 'x', 'account_name' => 'y', 'account_number' => '1234567890', 'is_active' => true, 'sort_order' => 0], (int) $account->getKey()),
            fn () => app(DeleteBankAccountAction::class)->execute($customer, (int) $account->getKey()),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('A customer changed a bank account.');
            } catch (AuthorizationException) {
                // expected
            }
        }
        $this->assertSame('0012345678', $account->fresh()->account_number);
    }

    public function test_the_admin_page_adds_edits_and_deletes_accounts_and_the_checklist_sees_them(): void
    {
        $this->actingAs($this->administrator());
        config(['methaq.payment.bank_transfer' => ['bank_name' => null, 'account_name' => null, 'account_number' => null, 'iban' => null]]);
        Livewire::test(SetupChecklist::class)->assertSee('غير مكتمل');

        Livewire::test(ManageBankAccounts::class)
            ->callAction('create', ['bank_name' => 'مصرف الصحاري', 'account_name' => 'ميثاق', 'account_number' => '5555555555', 'iban' => null, 'sort_order' => 1, 'is_active' => true])
            ->assertHasNoFormErrors();
        $account = BankAccount::query()->firstOrFail();
        $this->assertSame('مصرف الصحاري', $account->bank_name);

        Livewire::test(SetupChecklist::class)->assertSee('مضبوط');

        Livewire::test(ManageBankAccounts::class)
            ->callAction(TestAction::make('edit')->table($account), ['bank_name' => 'مصرف الوحدة', 'account_name' => 'ميثاق', 'account_number' => '5555555555', 'iban' => null, 'sort_order' => 1, 'is_active' => true])
            ->callAction(TestAction::make('delete')->table($account));

        $this->assertDatabaseCount('bank_accounts', 0);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
