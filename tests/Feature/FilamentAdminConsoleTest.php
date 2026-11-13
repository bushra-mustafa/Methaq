<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;
use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Widgets\RevenueOverview;
use App\Filament\Widgets\SetupChecklist;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class FilamentAdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Filament integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
    }

    public function test_the_dashboard_shows_approved_money_and_what_waits(): void
    {
        $this->actingAs($this->administrator());
        $this->order(['status' => OrderStatus::Completed, 'amount' => 25_000, 'completed_at' => now(), 'transaction_id' => 'manual-1']);
        $this->order(['amount' => 15_000]);

        $this->get('/admin')->assertOk();

        Livewire::test(RevenueOverview::class)
            ->assertSee('25')
            ->assertSee('1 قسيمة تنتظر قرارك');
    }

    public function test_the_checklist_flags_missing_bank_details_and_unpriced_templates(): void
    {
        $this->actingAs($this->administrator());
        config(['methaq.payment.bank_transfer' => ['bank_name' => null, 'account_name' => null, 'account_number' => null, 'iban' => null]]);

        Livewire::test(SetupChecklist::class)->assertSee('حساب استلام التحويلات')->assertSee('غير مكتمل');
    }

    public function test_events_can_be_suspended_with_a_reason_and_reinstated_and_both_are_audited(): void
    {
        $admin = $this->administrator();
        $event = Event::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ManageEvents::class)
            ->assertCanSeeTableRecords([$event])
            ->callAction(TestAction::make('suspend')->table($event), ['reason' => 'محتوى مخالف'])
            ->assertNotified();

        $this->assertNotNull($event->fresh()->suspended_at);
        $this->assertSame('محتوى مخالف', $event->fresh()->suspension_reason);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::EventSuspended->value, 'subject_id' => $event->getKey(), 'reason' => 'محتوى مخالف']);

        Livewire::test(ManageEvents::class)
            ->callAction(TestAction::make('reinstate')->table($event->fresh()))
            ->assertNotified();

        $this->assertNull($event->fresh()->suspended_at);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::EventReinstated->value, 'subject_id' => $event->getKey()]);
    }

    public function test_customers_can_be_suspended_but_administrators_cannot(): void
    {
        $admin = $this->administrator();
        $customer = User::factory()->create();
        $otherAdmin = $this->administrator();
        $this->actingAs($admin);

        Livewire::test(ManageUsers::class)
            ->assertCanSeeTableRecords([$customer])
            ->callAction(TestAction::make('suspend')->table($customer), ['reason' => 'احتيال'])
            ->assertNotified();

        $this->assertSame(UserStatus::Suspended, $customer->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::UserSuspended->value, 'subject_id' => $customer->getKey(), 'actor_id' => $admin->getKey()]);

        Livewire::test(ManageUsers::class)->assertActionHidden(TestAction::make('suspend')->table($otherAdmin));
        $this->assertSame(UserStatus::Active, $otherAdmin->fresh()->status);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('reinstate')->table($customer->fresh()))
            ->assertNotified();
        $this->assertSame(UserStatus::Active, $customer->fresh()->status);
    }

    public function test_the_audit_trail_is_listed_for_administrators_and_hidden_from_customers(): void
    {
        $admin = $this->administrator();
        $log = AuditLog::factory()->create(['action' => AuditAction::PaymentApproved->value]);

        $this->actingAs($admin);
        Livewire::test(ManageAuditLogs::class)->assertCanSeeTableRecords([$log]);

        auth()->logout();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/audit-logs')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/events')->assertForbidden();
    }

    public function test_receipts_export_to_a_utf8_csv_that_respects_the_filter_and_neutralises_formulas(): void
    {
        $this->actingAs($this->administrator());
        $event = Event::factory()->create(['title' => '=HYPERLINK("x")']);
        $pending = Order::factory()->manualTransfer()->create(['event_id' => $event->getKey(), 'user_id' => $event->user_id, 'amount' => 25_500]);
        $this->order(['status' => OrderStatus::Failed, 'failure_code' => 'not_received']);

        $component = Livewire::test(ManageOrders::class)->callAction('export');
        $component->assertFileDownloaded('methaq-receipts-'.now()->format('Y-m-d').'.csv');
        $csv = $component->effects['download']['content'] ?? '';
        $csv = base64_decode((string) $csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('25.500', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('not_received', $csv);
        $this->assertSame(2, substr_count($csv, "\n"));
        $this->assertNotNull($pending->getKey());
    }

    public function test_a_banner_warns_while_an_active_template_is_hidden_for_lacking_a_price(): void
    {
        $this->actingAs($this->administrator());
        Template::factory()->create(['is_active' => true, 'price_minor' => 0]);

        $this->get('/admin/templates')->assertOk()->assertSee('قالب مخفي عن العميلات لأنه بلا سعر');

        Template::query()->update(['price_minor' => 25_000]);
        $this->get('/admin/templates')->assertOk()->assertDontSee('قالب مخفي عن العميلات لأنه بلا سعر');
    }

    /** @param array<string, mixed> $attributes */
    private function order(array $attributes = []): Order
    {
        $event = Event::factory()->create();

        return Order::factory()->manualTransfer()->create([
            'event_id' => $event->getKey(),
            'user_id' => $event->user_id,
            ...$attributes,
        ]);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
