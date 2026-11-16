<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\Actions\GetInvitationProgressAction;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\GetEventPaymentAction;
use App\Domains\Payments\Actions\GetPaymentPreviewAction;
use App\Domains\Payments\Actions\UpdatePaymentReviewHoursAction;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\PaymentReviewSettings;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

final class InvitationJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires isolated MySQL.');
        }
        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh non-test database.');
        }
    }

    public function test_pending_receipt_has_a_deadline_and_reviewed_receipts_stop_being_overdue(): void
    {
        $this->freezeTime();
        $order = Order::factory()->manualTransfer()->create(['created_at' => now()->subHours(25)]);
        $settings = app(PaymentReviewSettings::class);
        $this->assertSame(24, $settings->hours());
        $this->assertSame(1, $settings->overdue()->count());
        $event = $order->event;
        $payment = app(GetEventPaymentAction::class)->execute($event->user, $event);
        $progress = app(GetInvitationProgressAction::class)->execute($event->user, $event, $payment);
        $this->assertSame('pending_review', $progress['state']);
        $this->assertSame(now()->subHour()->setTimezone($event->timezone)->toIso8601String(), $progress['reviewDueAt']);
        $order->forceFill(['status' => 'failed'])->save();
        $this->assertSame(0, $settings->overdue()->count());
    }

    public function test_status_respects_published_expired_and_suspended_states(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'is_paid' => true, 'paid_at' => now(), 'published_at' => now()]);
        foreach ([['published', null, 'published'], ['expired', null, 'expired'], ['published', now(), 'blocked']] as [$status, $suspended, $expected]) {
            $event->forceFill(['status' => $status, 'suspended_at' => $suspended])->save();
            $payment = app(GetEventPaymentAction::class)->execute($event->user, $event);
            $this->assertSame($expected, app(GetInvitationProgressAction::class)->execute($event->user, $event, $payment)['state']);
        }
    }

    public function test_preview_is_available_without_whatsapp_and_does_not_change_the_draft(): void
    {
        config(['cloudmsg.enabled' => false]);
        $event = Event::factory()->create()->fresh();
        $document = app(BlankDesignFactory::class)->make()->toArray();
        $design = EventDesign::query()->forceCreate([
            'event_id' => $event->id, 'schema_version' => 1, 'revision' => 1,
            'design_json' => $document['canvas'], 'scene_json' => $document['scene'], 'palette_json' => $document['palette'],
        ]);
        $preview = app(GetPaymentPreviewAction::class)->execute($event->user, $event);
        $this->assertSame(1, $preview['revision']);
        $this->assertEquals($document['canvas'], $preview['document']['canvas']);
        $this->assertEquals($document['scene'], $design->fresh()->scene_json);
    }

    public function test_customer_cannot_change_review_duration(): void
    {
        $this->expectException(AuthorizationException::class);
        app(UpdatePaymentReviewHoursAction::class)->execute(User::factory()->create(), 2);
    }

    public function test_confirmed_administrator_can_change_duration_with_an_audit_record(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();
        app(UpdatePaymentReviewHoursAction::class)->execute($admin, 6);
        $this->assertSame(6, app(PaymentReviewSettings::class)->hours());
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.review_hours_updated', 'actor_id' => $admin->id]);
    }

    public function test_progress_does_not_expose_another_owners_event(): void
    {
        $event = Event::factory()->create()->fresh();
        $payment = app(GetEventPaymentAction::class)->execute($event->user, $event);
        $this->expectException(AuthorizationException::class);
        app(GetInvitationProgressAction::class)->execute(User::factory()->create(), $event, $payment);
    }
}
