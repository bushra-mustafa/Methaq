<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\RejectPaymentAction;
use App\Domains\Payments\Enums\PaymentRejectionReason;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Actions\RefreshWhatsAppMessageAction;
use App\Domains\Users\Actions\ResendWhatsAppMessageAction;
use App\Domains\Users\Actions\TestCloudMsgConnectionAction;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\WhatsAppFailures;
use App\Domains\Users\Services\WhatsAppOutbox;
use App\Filament\Pages\WhatsAppSettings;
use App\Filament\Resources\WhatsAppMessages\Pages\ManageWhatsAppMessages;
use App\Jobs\SendWhatsAppMessage;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class WhatsAppOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires isolated MySQL.');
        }
        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a non-test database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
        config(['cloudmsg.enabled' => true, 'cloudmsg.api_key' => 'test-secret', 'cloudmsg.instance_id' => 'inst_test', 'cloudmsg.admin_phone' => '+218910000099']);
        Http::preventStrayRequests();
    }

    public function test_retry_from_panel_uses_corrected_admin_number_and_records_only_one_replacement(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $source = $this->failed(['recipient' => '+218910000088']);
        Livewire::test(ManageWhatsAppMessages::class)->callAction(TestAction::make('retry')->table($source), ['recipient' => '+218910000099'])->assertHasNoFormErrors();
        $copy = $source->resends()->sole();
        $this->assertSame('+218910000099', $copy->recipient);
        $this->assertSame($source->body, $copy->body);
        $this->assertSame('failed', $source->refresh()->status);
        $again = app(ResendWhatsAppMessageAction::class)->execute($admin, $source->id, '+218910000099');
        $this->assertSame($copy->id, $again->id);
        $this->assertDatabaseCount('whatsapp_messages', 2);
        $this->assertSame(0, WhatsAppFailures::count());
        Http::assertNothingSent();
    }

    public function test_provider_failed_message_uses_the_resend_endpoint_with_a_new_idempotency_key(): void
    {
        $source = $this->failed(['provider_message_id' => 'msg_original']);
        $copy = app(ResendWhatsAppMessageAction::class)->execute($this->admin(), $source->id, $source->recipient);
        Http::fake(['cloudmsg.impaact.io/*' => Http::response(['data' => ['id' => 'msg_copy']], 202)]);
        $job = new SendWhatsAppMessage($copy->id);
        $job->handle(app(CloudMsg::class));
        $job->handle(app(CloudMsg::class));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages/msg_original/resend') && $request->hasHeader('Idempotency-Key', 'retry-'.$source->id));
        $this->assertSame('queued', $copy->refresh()->status);
    }

    public function test_changing_the_admin_recipient_sends_a_new_message_instead_of_reusing_the_old_recipient(): void
    {
        $source = $this->failed(['provider_message_id' => 'msg_old', 'recipient' => '+218910000088']);
        $copy = app(ResendWhatsAppMessageAction::class)->execute($this->admin(), $source->id, '+218910000099');
        Http::fake(['cloudmsg.impaact.io/*' => Http::response(['data' => ['id' => 'msg_new']], 202)]);
        (new SendWhatsAppMessage($copy->id))->handle(app(CloudMsg::class));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/messages') && $request['to'] === '+218910000099');
    }

    public function test_retries_refuse_expired_verification_queued_and_changed_customer_recipients(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create(['whatsapp_phone' => '+218910000077', 'whatsapp_verified_at' => now()]);
        foreach ([['kind' => 'verification'], ['expires_at' => now()->subMinute()], ['status' => 'queued'], ['user_id' => $owner->id, 'recipient' => '+218910000088']] as $attributes) {
            $source = $this->failed($attributes);
            try {
                app(ResendWhatsAppMessageAction::class)->execute($admin, $source->id, $source->user_id ? $source->recipient : '+218910000099');
                $this->fail('An unsafe retry was accepted.');
            } catch (ValidationException) {
                $this->assertSame(0, $source->resends()->count());
            }
        }
        Http::assertNothingSent();
    }

    public function test_rejecting_receipt_queues_one_reason_message_and_leaves_event_unpaid(): void
    {
        $owner = User::factory()->create(['whatsapp_phone' => '+218910000077', 'whatsapp_verified_at' => now()]);
        $event = Event::factory()->create(['user_id' => $owner->id]);
        $order = Order::factory()->manualTransfer()->create(['user_id' => $owner->id, 'event_id' => $event->id, 'whatsapp_phone' => $owner->whatsapp_phone]);
        $admin = $this->admin();
        $reject = app(RejectPaymentAction::class);
        $reject->execute($admin, $order->id, PaymentRejectionReason::UnclearReceipt);
        $reject->execute($admin, $order->id, PaymentRejectionReason::UnclearReceipt);
        $message = WhatsAppMessage::query()->where('deduplication_key', 'payment-rejected-'.$order->id)->sole();
        $this->assertSame('rejection', $message->kind);
        $this->assertSame($owner->whatsapp_phone, $message->recipient);
        $this->assertStringContainsString('القسيمة غير واضحة', $message->body);
        $this->assertStringContainsString('/app/events/'.$event->id.'/payment', $message->body);
        $this->assertFalse($event->refresh()->is_paid);
        $this->assertDatabaseCount('whatsapp_messages', 1);
    }

    public function test_test_button_checks_status_and_sends_once_even_while_regular_messages_are_disabled(): void
    {
        config(['cloudmsg.enabled' => false]);
        $this->actingAs($this->admin());
        Http::fake([
            '*/instances/inst_test/status' => Http::response(['data' => ['status' => 'ready']]),
            '*/messages' => Http::response(['data' => ['id' => 'msg_test_button']], 202),
        ]);
        Livewire::test(WhatsAppSettings::class)->callAction('testConnection', ['recipient' => '+218910000099'])->assertHasNoFormErrors();
        $message = WhatsAppMessage::query()->sole();
        $this->assertSame('test', $message->kind);
        $this->assertSame('queued', $message->status);
        app(TestCloudMsgConnectionAction::class)->execute(auth()->user(), '+218910000099');
        Http::assertSentCount(3); // Two status checks, one outbound test.
        $this->assertFalse(config('cloudmsg.enabled'));
        app(WhatsAppOutbox::class)->record('normal-disabled', '+218910000099', 'Must not send');
        $this->assertDatabaseCount('whatsapp_messages', 1);
    }

    public function test_unready_or_incorrect_configuration_sends_no_test_and_never_leaks_provider_text(): void
    {
        $admin = $this->admin();
        Http::fake(['*' => Http::response(['data' => ['status' => 'disconnected'], 'debug' => 'SECRET-MUST-NOT-LEAK'])]);
        try {
            app(TestCloudMsgConnectionAction::class)->execute($admin, '+218910000099');
            $this->fail('Unready number was accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringNotContainsString('SECRET-MUST-NOT-LEAK', $exception->getMessage());
        }
        Http::assertSentCount(1);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_failure_alert_appears_in_admin_and_disappears_when_a_retry_is_queued(): void
    {
        $this->actingAs($admin = $this->admin());
        $source = $this->failed(['failure_code' => 'whatsapp_recipient_not_registered']);
        $this->get('/admin')->assertOk()->assertSee('تعذّر إرسال 1');
        Livewire::test(ManageWhatsAppMessages::class)->assertSee('رقم المستلم غير مسجّل في واتساب');
        app(ResendWhatsAppMessageAction::class)->execute($admin, $source->id, '+218910000099');
        $this->get('/admin')->assertOk()->assertDontSee('تعذّر إرسال 1');
    }

    public function test_only_confirmed_active_admins_can_retry_or_send_test_messages(): void
    {
        $source = $this->failed();
        foreach ([User::factory()->create(), User::factory()->create(['role' => 'admin'])] as $actor) {
            foreach (['retry', 'test', 'refresh'] as $operation) {
                try {
                    if ($operation === 'retry') {
                        app(ResendWhatsAppMessageAction::class)->execute($actor, $source->id, '+218910000099');
                    } elseif ($operation === 'refresh') {
                        app(RefreshWhatsAppMessageAction::class)->execute($actor, $source->id);
                    } else {
                        app(TestCloudMsgConnectionAction::class)->execute($actor, '+218910000099');
                    }
                    $this->fail('Unauthorized action accepted.');
                } catch (AuthorizationException) {
                    $this->assertDatabaseCount('whatsapp_messages', 1);
                }
            }
        }
        Http::assertNothingSent();
    }

    public function test_refresh_recovers_old_provider_failures_so_they_can_be_retried(): void
    {
        $source = $this->failed(['status' => 'queued', 'provider_message_id' => 'msg_old_failure']);
        Http::fake(['*/messages/msg_old_failure' => Http::response(['data' => ['status' => 'failed', 'failure_code' => 'whatsapp_recipient_not_registered']])]);
        $this->actingAs($this->admin());
        Livewire::test(ManageWhatsAppMessages::class)->callAction(TestAction::make('refreshStatus')->table($source))->assertHasNoFormErrors();
        $this->assertSame('failed', $source->refresh()->status);
        $this->assertSame('whatsapp_recipient_not_registered', $source->failure_code);
        $this->assertSame(1, WhatsAppFailures::count());
    }

    private function failed(array $attributes = []): WhatsAppMessage
    {
        return WhatsAppMessage::query()->forceCreate(['deduplication_key' => 'failed-'.bin2hex(random_bytes(8)), 'kind' => 'receipt', 'recipient' => '+218910000099', 'body' => 'إشعار قسيمة', 'status' => 'failed', 'expires_at' => now()->addDay(), 'next_attempt_at' => now(), ...$attributes]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(EnableTwoFactorAuthentication::class)($admin, force: true);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
