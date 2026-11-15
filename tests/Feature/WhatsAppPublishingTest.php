<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\SaveDesignAction;
use App\Domains\Editor\DTOs\SaveDesignData;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\Actions\PrepareInvitationAction;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\ApprovePaymentAction;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Users\Actions\ProcessCloudMsgWebhookAction;
use App\Domains\Users\Actions\UpdateUserProfileAction;
use App\Domains\Users\Models\CloudMsgWebhook;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\WhatsAppOutbox;
use App\Filament\Resources\WhatsAppMessages\WhatsAppMessageResource;
use App\Jobs\ProcessCloudMsgWebhook;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

final class WhatsAppPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires the isolated MySQL test database.');
        }
        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a non-test database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['cloudmsg.enabled' => true, 'cloudmsg.instance_id' => 'inst_test', 'cloudmsg.api_key' => 'test-key', 'cloudmsg.webhook_secret' => 'test-secret', 'cloudmsg.admin_phone' => '+218910000099', 'methaq.payment.bank_transfer' => ['bank_name' => 'Test bank', 'account_name' => 'Methaq', 'account_number' => '123', 'iban' => null]]);
        Storage::fake('local');
        Storage::fake('public');
        Http::preventStrayRequests();
    }

    public function test_receipt_approval_and_whatsapp_reply_publish_once_and_queue_the_link(): void
    {
        $event = $this->draft();
        $this->actingAs($event->user)->post('/app/events/'.$event->id.'/payment', [
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'render' => $this->render(), 'expectedRevision' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::query()->sole();
        $this->assertSame($event->user->whatsapp_phone, $order->whatsapp_phone);
        Storage::disk('local')->assertExists($event->design->refresh()->prepared_render_path);
        $this->assertDatabaseHas('whatsapp_messages', ['deduplication_key' => 'receipt-'.$order->id]);

        $admin = User::factory()->create(['role' => 'admin']);
        app(ApprovePaymentAction::class)->execute($admin, $order->id);
        app(ApprovePaymentAction::class)->execute($admin, $order->id);
        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        $this->assertSame(1, WhatsAppMessage::query()->where('deduplication_key', 'payment-approved-'.$order->id)->count());
        $this->incoming($event->user, 'نشر', 'evt_publish');
        $this->incoming($event->user, 'نشر', 'evt_publish');
        $this->assertSame(EventStatus::Published, $event->refresh()->status);
        $this->assertSame(1, WhatsAppMessage::query()->where('deduplication_key', 'invitation-published-'.$event->id)->count());
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->assertStringContainsString($event->subdomain, WhatsAppMessage::query()->where('deduplication_key', 'invitation-published-'.$event->id)->sole()->body);
        $this->incoming($event->user, 'نشر', 'evt_repeat');
        $this->assertStringContainsString('منشورة بالفعل', WhatsAppMessage::query()->where('deduplication_key', 'reply-'.hash('sha256', 'evt_repeat'))->sole()->body);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_unpaid_stale_expired_and_suspended_invitations_cannot_be_published(): void
    {
        $event = $this->draft();
        $this->prepare($event);
        $this->incoming($event->user, 'نشر '.$event->id, 'evt_unpaid');
        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        $this->pay($event);
        $document = app(BlankDesignFactory::class)->make();
        app(SaveDesignAction::class)->execute($event->user, $event, new SaveDesignData($document, 1));
        $this->incoming($event->user, 'نشر '.$event->id, 'evt_stale');
        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        $this->assertStringContainsString('تغيّر التصميم', WhatsAppMessage::query()->where('deduplication_key', 'reply-'.hash('sha256', 'evt_stale'))->sole()->body);
        $this->prepare($event, 2);
        $event->forceFill(['suspended_at' => now()])->save();
        $this->incoming($event->user, 'نشر '.$event->id, 'evt_suspended');
        $event->forceFill(['suspended_at' => null, 'event_date' => now()->subDay()])->save();
        $this->incoming($event->user, 'نشر '.$event->id, 'evt_past');
        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_multiple_invitations_need_an_id_and_foreign_ids_are_never_published(): void
    {
        $first = $this->draft();
        $second = $this->draft($first->user);
        foreach ([$first, $second] as $event) {
            $this->prepare($event);
            $this->pay($event);
        }
        $this->incoming($first->user, 'نشر', 'evt_choose');
        $this->assertStringContainsString('أكثر من دعوة', WhatsAppMessage::query()->where('deduplication_key', 'reply-'.hash('sha256', 'evt_choose'))->sole()->body);
        $stranger = $this->draft();
        $this->incoming($stranger->user, 'نشر '.$first->id, 'evt_foreign');
        $this->assertSame(EventStatus::Draft, $first->refresh()->status);
        $this->incoming($first->user, 'نشر '.$second->id, 'evt_specific');
        $this->assertSame(EventStatus::Published, $second->refresh()->status);
        $this->assertSame(EventStatus::Draft, $first->refresh()->status);
    }

    public function test_unknown_unverified_group_and_lid_senders_cannot_publish(): void
    {
        $event = $this->draft();
        $this->prepare($event);
        $this->pay($event);
        $event->user->forceFill(['whatsapp_verified_at' => null])->save();
        $this->incoming($event->user, 'نشر', 'evt_unverified');
        $event->user->forceFill(['whatsapp_verified_at' => now()])->save();
        foreach ([['from' => '1234567890@lid', 'chat_id' => '1234567890@lid'], ['chat_id' => '123@g.us'], ['from' => '218910000088@c.us', 'chat_id' => '218910000088@c.us']] as $i => $extra) {
            $this->incoming($event->user, 'نشر', 'evt_bad'.$i, $extra);
        }
        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_verification_codes_expire_limit_guesses_and_are_not_stored_in_plaintext(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/app/whatsapp/send-code', ['phone' => '+218910000001'])->assertSessionHasNoErrors();
        $message = WhatsAppMessage::query()->sole();
        preg_match('/[0-9]{6}/', $message->body, $code);
        $this->assertNotNull($user->refresh()->whatsapp_code_hash);
        $this->assertStringNotContainsString($code[0], DB::table('whatsapp_messages')->value('body'));
        $this->assertArrayNotHasKey('whatsapp_code_hash', $user->toArray());
        $this->post('/app/whatsapp/send-code', ['phone' => '+218910000001'])->assertSessionHasErrors('phone');
        $wrong = $code[0] === '111111' ? '222222' : '111111';
        $this->post('/app/whatsapp/confirm', ['code' => $wrong])->assertSessionHasErrors('code');
        $this->post('/app/whatsapp/confirm', ['code' => $code[0]])->assertSessionHasNoErrors();
        $this->assertNotNull($user->refresh()->whatsapp_verified_at);
        $this->travel(61)->seconds();
        $this->post('/app/whatsapp/send-code', ['phone' => '+218910000001'])->assertSessionHasNoErrors();
        $this->assertNull($user->refresh()->whatsapp_verified_at);
        $this->travel(11)->minutes();
        $this->post('/app/whatsapp/confirm', ['code' => $code[0]])->assertSessionHasErrors('code');
        $this->assertNull($user->refresh()->whatsapp_verified_at);
    }

    public function test_receipt_requires_verified_phone_and_a_current_prepared_render(): void
    {
        $event = $this->draft();
        $this->actingAs($event->user);
        $this->post('/app/events/'.$event->id.'/payment', ['receipt' => UploadedFile::fake()->image('receipt.jpg')])->assertSessionHasErrors('render');
        $event->user->forceFill(['whatsapp_verified_at' => null])->save();
        $this->post('/app/events/'.$event->id.'/payment', ['receipt' => UploadedFile::fake()->image('receipt.jpg'), 'render' => $this->render(), 'expectedRevision' => 1])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    public function test_payment_page_supplies_preparation_and_owners_can_refresh_it_without_paying_again(): void
    {
        $event = $this->draft();
        $this->pay($event);
        $this->actingAs($event->user)->withoutVite()->get('/app/events/'.$event->id.'/payment')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('whatsapp.enabled', true)->where('whatsapp.verified', true)->where('preparation.revision', 1));
        $this->post('/app/events/'.$event->id.'/prepare', ['render' => $this->render(), 'expectedRevision' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, $event->design->refresh()->prepared_revision);
        $this->assertDatabaseCount('orders', 1);
        $this->actingAs(User::factory()->create())->post('/app/events/'.$event->id.'/prepare', ['render' => $this->render(), 'expectedRevision' => 1])->assertForbidden();
    }

    public function test_website_publication_also_queues_the_link_and_a_rollback_queues_nothing(): void
    {
        $event = $this->draft();
        $this->pay($event);
        $this->actingAs($event->user)->post('/app/events/'.$event->id.'/publish', ['render' => $this->render(), 'expectedRevision' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('whatsapp_messages', ['deduplication_key' => 'invitation-published-'.$event->id]);
        DB::beginTransaction();
        app(WhatsAppOutbox::class)->record('rolled-back', '+218910000099', 'not sent');
        DB::rollBack();
        $this->assertDatabaseMissing('whatsapp_messages', ['deduplication_key' => 'rolled-back']);
    }

    public function test_transient_errors_retry_with_the_same_key_and_delivery_events_update_status(): void
    {
        app(WhatsAppOutbox::class)->record('test-retry-key', '+218910000099', 'Hello');
        $message = WhatsAppMessage::query()->sole();
        Http::fake(['cloudmsg.impaact.io/*' => Http::sequence()->push(['error' => ['code' => 'rate_limited']], 429, ['Retry-After' => '120'])->push(['data' => ['id' => 'msg_test']], 202)]);
        $job = new SendWhatsAppMessage($message->id);
        $job->handle(app(CloudMsg::class));
        $this->assertSame('pending', $message->refresh()->status);
        $this->travel(121)->seconds();
        $job->handle(app(CloudMsg::class));
        $this->assertSame('queued', $message->refresh()->status);
        $job->handle(app(CloudMsg::class));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'test-retry-key') && $request->hasHeader('Authorization', 'Bearer test-key'));
        $this->signedWebhook(['id' => 'evt_ack', 'type' => 'message.ack', 'data' => ['instance_id' => 'inst_test', 'message_id' => 'msg_test']]);
        app(ProcessCloudMsgWebhookAction::class)->execute(CloudMsgWebhook::query()->sole()->id);
        $this->assertSame('acknowledged', $message->refresh()->status);
    }

    public function test_permanent_errors_and_expired_notifications_are_not_retried(): void
    {
        app(WhatsAppOutbox::class)->record('test-bad-key', '+218910000099', 'Hello');
        $message = WhatsAppMessage::query()->sole();
        Http::fake(['cloudmsg.impaact.io/*' => Http::response(['error' => ['code' => 'unauthorized']], 401)]);
        $job = new SendWhatsAppMessage($message->id);
        $job->handle(app(CloudMsg::class));
        $job->handle(app(CloudMsg::class));
        $this->assertSame('failed', $message->refresh()->status);
        Http::assertSentCount(1);
        app(WhatsAppOutbox::class)->record('test-expired-key', '+218910000099', 'Expired', minutes: 1);
        $this->travel(61)->seconds();
        (new SendWhatsAppMessage(WhatsAppMessage::query()->where('deduplication_key', 'test-expired-key')->sole()->id))->handle(app(CloudMsg::class));
        Http::assertSentCount(1);
    }

    public function test_five_wrong_codes_lock_confirmation_without_reserving_an_unverified_number(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/app/whatsapp/send-code', ['phone' => '+218910000001'])->assertSessionHasNoErrors();
        preg_match('/[0-9]{6}/', WhatsAppMessage::query()->sole()->body, $code);
        $wrong = $code[0] === '111111' ? '222222' : '111111';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/app/whatsapp/confirm', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post('/app/whatsapp/confirm', ['code' => $code[0]])->assertSessionHasErrors('code');
        $this->assertSame(5, $user->refresh()->whatsapp_code_attempts);
        $this->assertNull($user->whatsapp_verified_at);
        $this->actingAs(User::factory()->create())->post('/app/whatsapp/send-code', ['phone' => '+218910000001'])->assertSessionHasNoErrors();
    }

    public function test_profile_phone_changes_revoke_verification_and_cancel_pending_notifications(): void
    {
        $event = $this->draft();
        app(WhatsAppOutbox::class)->published($event);
        app(UpdateUserProfileAction::class)->update($event->user, [
            'name' => $event->user->name, 'email' => $event->user->email, 'phone' => '+218920000001',
        ]);
        $this->assertNull($event->user->refresh()->whatsapp_verified_at);
        $message = WhatsAppMessage::query()->sole();
        (new SendWhatsAppMessage($message->id))->handle(app(CloudMsg::class));
        $this->assertSame('cancelled', $message->refresh()->status);
        Http::assertNothingSent();
    }

    public function test_delivery_callback_that_arrives_before_provider_id_is_recorded_is_retried(): void
    {
        $this->signedWebhook(['id' => 'evt_early_ack', 'type' => 'message.ack', 'data' => ['instance_id' => 'inst_test', 'message_id' => 'msg_early']]);
        $webhook = CloudMsgWebhook::query()->sole();
        app(ProcessCloudMsgWebhookAction::class)->execute($webhook->id);
        $this->assertSame('pending', $webhook->refresh()->status);
        app(WhatsAppOutbox::class)->record('test-early-callback', '+218910000099', 'Hello');
        $message = WhatsAppMessage::query()->sole();
        $message->forceFill(['provider_message_id' => 'msg_early', 'status' => 'queued'])->save();
        $this->travel(61)->seconds();
        app(ProcessCloudMsgWebhookAction::class)->execute($webhook->id);
        $this->assertSame('processed', $webhook->refresh()->status);
        $this->assertSame('acknowledged', $message->refresh()->status);
    }

    public function test_only_confirmed_administrators_can_monitor_notifications(): void
    {
        $resource = WhatsAppMessageResource::class;
        $this->actingAs(User::factory()->create());
        $this->assertFalse($resource::canViewAny());
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->assertFalse($resource::canViewAny());
        app(EnableTwoFactorAuthentication::class)($admin, force: true);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();
        $this->assertTrue($resource::canViewAny());
        $this->get('/admin/whats-app-messages')->assertOk();
    }

    public function test_scheduler_dispatches_inbox_and_outbox_work_on_the_database_queue(): void
    {
        Queue::fake();
        app(WhatsAppOutbox::class)->record('test-scheduler-message', '+218910000099', 'Hello');
        $this->signedWebhook(['id' => 'evt_dispatch', 'type' => 'test.event', 'data' => ['instance_id' => 'inst_test']]);
        $this->artisan('methaq:dispatch-whatsapp')->assertSuccessful();
        Queue::assertPushed(SendWhatsAppMessage::class, fn ($job) => $job->connection === 'database');
        Queue::assertPushed(ProcessCloudMsgWebhook::class, fn ($job) => $job->connection === 'database');
    }

    public function test_a_confirmed_phone_cannot_be_claimed_by_another_account(): void
    {
        $event = $this->draft();
        $this->actingAs(User::factory()->create())->post('/app/whatsapp/send-code', ['phone' => $event->user->whatsapp_phone])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }

    private function draft(?User $user = null): Event
    {
        static $sequence = 0;
        $sequence++;
        $user ??= User::factory()->create(['phone' => '+21891'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT), 'whatsapp_phone' => '+21891'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT), 'whatsapp_verified_at' => now()]);
        $event = Event::factory()->create(['user_id' => $user->id, 'template_id' => Template::factory()->create(['price_minor' => 25000])->id]);
        EventDesign::query()->forceCreate(['event_id' => $event->id, ...app(BlankDesignFactory::class)->make()->toStorageColumns(), 'revision' => 1]);

        return $event->load(['user', 'template', 'design']);
    }

    private function pay(Event $event): void
    {
        Order::factory()->manualTransfer()->create(['event_id' => $event->id, 'user_id' => $event->user_id, 'status' => OrderStatus::Completed, 'completed_at' => now(), 'transaction_id' => 'manual-'.$event->id, 'whatsapp_phone' => $event->user->whatsapp_phone, 'entitlements' => app(ManualPaymentEligibility::class)->entitlements($event)]);
        $event->forceFill(['is_paid' => true, 'paid_at' => now()])->save();
    }

    private function render(): UploadedFile
    {
        return UploadedFile::fake()->image('card.png', 1080, 1920);
    }

    private function prepare(Event $event, int $revision = 1): void
    {
        app(PrepareInvitationAction::class)->execute($event->user, $event, $this->render(), $revision);
    }

    private function incoming(User $user, string $text, string $id, array $extra = []): void
    {
        $from = ltrim($user->whatsapp_phone, '+').'@c.us';
        $payload = ['id' => $id, 'type' => 'message.received', 'data' => ['instance_id' => 'inst_test', 'type' => 'text', 'from' => $from, 'chat_id' => $from, 'text' => ['body' => $text], ...$extra]];
        $this->signedWebhook($payload);
        app(ProcessCloudMsgWebhookAction::class)->execute(CloudMsgWebhook::query()->where('provider_event_id', $id)->sole()->id);
    }

    private function signedWebhook(array $payload): void
    {
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $this->call('POST', '/hooks/cloudmsg', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_CLOUDMSG_TIMESTAMP' => $timestamp, 'HTTP_X_CLOUDMSG_EVENT_ID' => $payload['id'], 'HTTP_X_CLOUDMSG_SIGNATURE' => 'v1='.hash_hmac('sha256', $timestamp.'.'.$raw, 'test-secret')], $raw)->assertNoContent();
    }
}
