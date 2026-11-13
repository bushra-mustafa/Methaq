<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\ApprovePaymentAction;
use App\Domains\Payments\Actions\RejectPaymentAction;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentRejectionReason;
use App\Domains\Payments\Exceptions\PaymentReviewException;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

final class PaymentReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Payment review integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['methaq.payment.bank_transfer' => ['bank_name' => null, 'account_name' => null, 'account_number' => '0012345678', 'iban' => null]]);
    }

    public function test_approval_marks_the_order_completed_and_the_event_paid_with_an_audit_entry_and_never_publishes(): void
    {
        $admin = $this->administrator();
        $order = $this->pendingOrder();

        app(ApprovePaymentAction::class)->execute($admin, (int) $order->getKey());

        $order->refresh();
        $event = $order->event->refresh();
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame('manual-'.$order->getKey(), $order->transaction_id);
        $this->assertNotNull($order->completed_at);
        $this->assertSame($admin->getKey(), $order->reviewed_by);
        $this->assertNotNull($order->reviewed_at);
        $this->assertTrue($event->is_paid);
        $this->assertNotNull($event->paid_at);
        $this->assertSame('draft', $event->status->value);
        $this->assertNull($event->published_at);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->getKey(), 'action' => 'payment.approved', 'subject_type' => 'order', 'subject_id' => $order->getKey()]);
    }

    public function test_approving_twice_changes_nothing_and_logs_once(): void
    {
        $admin = $this->administrator();
        $order = $this->pendingOrder();

        app(ApprovePaymentAction::class)->execute($admin, (int) $order->getKey());
        $paidAt = $order->event->refresh()->paid_at;
        app(ApprovePaymentAction::class)->execute($admin, (int) $order->getKey());

        $this->assertEquals($paidAt, $order->event->refresh()->paid_at);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'payment.approved')->count());
    }

    public function test_rejection_fails_the_order_keeps_the_event_unpaid_and_allows_a_new_receipt(): void
    {
        $admin = $this->administrator();
        $order = $this->pendingOrder();

        app(RejectPaymentAction::class)->execute($admin, (int) $order->getKey(), PaymentRejectionReason::UnclearReceipt);

        $order->refresh();
        $this->assertSame(OrderStatus::Failed, $order->status);
        $this->assertSame('unclear_receipt', $order->failure_code);
        $this->assertSame($admin->getKey(), $order->reviewed_by);
        $this->assertFalse($order->event->refresh()->is_paid);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.rejected', 'subject_id' => $order->getKey(), 'reason' => 'القسيمة غير واضحة']);

        $this->actingAs($order->user)->withoutVite()->get("/app/events/{$order->event_id}/payment")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('payment.state', 'rejected')
                ->where('payment.canSubmit', true)
                ->where('payment.rejectionMessage', PaymentRejectionReason::UnclearReceipt->ownerMessage()));
    }

    public function test_an_approved_order_cannot_be_rejected_and_a_rejected_one_cannot_be_approved(): void
    {
        $admin = $this->administrator();
        $approved = $this->pendingOrder();
        app(ApprovePaymentAction::class)->execute($admin, (int) $approved->getKey());

        try {
            app(RejectPaymentAction::class)->execute($admin, (int) $approved->getKey(), PaymentRejectionReason::NotReceived);
            $this->fail('An approved order must not be rejectable.');
        } catch (PaymentReviewException) {
            $this->assertSame(OrderStatus::Completed, $approved->refresh()->status);
        }

        $rejected = $this->pendingOrder();
        app(RejectPaymentAction::class)->execute($admin, (int) $rejected->getKey(), PaymentRejectionReason::AmountMismatch);

        $this->expectException(PaymentReviewException::class);
        app(ApprovePaymentAction::class)->execute($admin, (int) $rejected->getKey());
    }

    public function test_only_an_active_administrator_can_decide(): void
    {
        $order = $this->pendingOrder();
        $suspended = User::factory()->create(['role' => UserRole::Admin, 'status' => 'suspended']);

        foreach ([$order->user, $suspended] as $actor) {
            try {
                app(ApprovePaymentAction::class)->execute($actor, (int) $order->getKey());
                $this->fail('Only an active administrator may approve.');
            } catch (AuthorizationException) {
                // expected
            }
        }

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
        $this->assertFalse($order->event->refresh()->is_paid);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_receipt_is_streamed_to_administrators_only(): void
    {
        $order = $this->pendingOrder();
        Storage::disk('local')->put($order->receipt_path, 'receipt-bytes');
        $url = "/admin/receipts/{$order->getKey()}";

        $this->get($url)->assertRedirect();
        $this->actingAs($order->user)->get($url)->assertForbidden();

        $response = $this->actingAs($this->administrator())->get($url);
        $response->assertOk();
        $this->assertSame('receipt-bytes', $response->streamedContent());
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_a_pdf_receipt_is_sent_as_a_download(): void
    {
        $order = $this->pendingOrder(['receipt_path' => 'payments/receipts/9/receipt.pdf']);
        Storage::disk('local')->put($order->receipt_path, '%PDF-1.4');

        $response = $this->actingAs($this->administrator())->get("/admin/receipts/{$order->getKey()}");

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    /** @param array<string, mixed> $attributes */
    private function pendingOrder(array $attributes = []): Order
    {
        $template = Template::factory()->create(['price_minor' => 25_000]);
        $event = Event::factory()->create(['template_id' => $template->getKey()]);

        return Order::factory()->manualTransfer()->create(['event_id' => $event->getKey(), ...$attributes])->load(['user', 'event']);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
