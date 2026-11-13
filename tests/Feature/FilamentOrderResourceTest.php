<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentRejectionReason;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class FilamentOrderResourceTest extends TestCase
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

    public function test_pending_manual_receipts_are_listed_without_create_edit_or_delete_and_other_gateways_are_hidden(): void
    {
        $pending = $this->order();
        $stripe = Order::factory()->create(['event_id' => Event::factory()->create()->getKey()]);
        $this->actingAs($this->administrator());

        $this->get('/admin/orders')->assertOk()->assertSee('قسائم التحويل');

        Livewire::test(ManageOrders::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$stripe])
            ->assertActionDoesNotExist('create');
        $this->assertFalse(OrderResource::canCreate());
    }

    public function test_the_status_filter_defaults_to_pending_and_can_show_decided_receipts(): void
    {
        $pending = $this->order();
        $failed = $this->order(['status' => OrderStatus::Failed, 'failure_code' => 'not_received']);
        $this->actingAs($this->administrator());

        Livewire::test(ManageOrders::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$failed])
            ->filterTable('status', OrderStatus::Failed->value)
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_approve_action_pays_the_event_and_hides_the_buttons_afterwards(): void
    {
        $admin = $this->administrator();
        $order = $this->order();
        $this->actingAs($admin);

        Livewire::test(ManageOrders::class)
            ->callAction(TestAction::make('approve')->table($order));

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
        $this->assertTrue($order->event->refresh()->is_paid);

        Livewire::test(ManageOrders::class)
            ->filterTable('status', OrderStatus::Completed->value)
            ->assertActionHidden(TestAction::make('approve')->table($order))
            ->assertActionHidden(TestAction::make('reject')->table($order));
    }

    public function test_reject_action_needs_a_reason_and_leaves_the_event_unpaid(): void
    {
        $order = $this->order();
        $this->actingAs($this->administrator());

        Livewire::test(ManageOrders::class)
            ->callAction(TestAction::make('reject')->table($order), data: ['reason' => null])
            ->assertHasFormErrors(['reason' => 'required']);

        Livewire::test(ManageOrders::class)
            ->callAction(TestAction::make('reject')->table($order), data: ['reason' => PaymentRejectionReason::NotReceived->value])
            ->assertHasNoFormErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertSame('not_received', $order->failure_code);
        $this->assertFalse($order->event->refresh()->is_paid);
    }

    public function test_the_navigation_badge_counts_pending_manual_receipts_only(): void
    {
        $this->assertNull(OrderResource::getNavigationBadge());

        $this->order();
        $this->order();
        $this->order(['status' => OrderStatus::Failed]);
        Order::factory()->create(['event_id' => Event::factory()->create()->getKey()]);

        $this->assertSame('2', OrderResource::getNavigationBadge());
    }

    public function test_customers_and_administrators_without_two_factor_cannot_open_the_orders_page(): void
    {
        $this->order();

        $this->actingAs(User::factory()->create())->get('/admin/orders')->assertForbidden();

        $withoutTwoFactor = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($withoutTwoFactor)->get('/admin/orders')->assertRedirect(route('app.profile'));
    }

    /** @param array<string, mixed> $attributes */
    private function order(array $attributes = []): Order
    {
        return Order::factory()->manualTransfer()->create(['event_id' => Event::factory()->create()->getKey(), ...$attributes])->load(['user', 'event']);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
