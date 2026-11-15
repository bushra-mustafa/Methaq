<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\Actions\PrepareInvitationAction;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Users\Actions\ProcessCloudMsgWebhookAction;
use App\Domains\Users\Models\CloudMsgWebhook;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;

final class WhatsAppConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    private ?int $eventId = null;

    protected function tearDown(): void
    {
        // Older down migrations narrow gateway constraints: remove this committed fixture first.
        if ($this->eventId !== null) {
            DB::table('orders')->where('event_id', $this->eventId)->delete();
        }
        parent::tearDown();
    }

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires isolated MySQL and pcntl.');
        }
        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a non-test database.');
        }
    }

    public function test_two_simultaneous_whatsapp_commands_publish_one_invitation_and_one_link_notification(): void
    {
        config(['cloudmsg.enabled' => true, 'cloudmsg.instance_id' => 'inst_test']);
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create(['whatsapp_phone' => '+218910000001', 'whatsapp_verified_at' => now()]);
        $event = Event::factory()->create(['user_id' => $user->id, 'template_id' => Template::factory()->create(['price_minor' => 25000])->id]);
        $this->eventId = $event->id;
        EventDesign::query()->forceCreate(['event_id' => $event->id, ...app(BlankDesignFactory::class)->make()->toStorageColumns(), 'revision' => 1]);
        Order::factory()->manualTransfer()->create([
            'event_id' => $event->id, 'user_id' => $user->id, 'status' => OrderStatus::Completed,
            'completed_at' => now(), 'transaction_id' => 'manual-concurrent',
            'entitlements' => app(ManualPaymentEligibility::class)->entitlements($event),
        ]);
        $event->forceFill(['is_paid' => true, 'paid_at' => now()])->save();
        app(PrepareInvitationAction::class)->execute($user, $event, UploadedFile::fake()->image('card.png', 1080, 1920), 1);
        $ids = [];
        foreach (['evt_parallel_a', 'evt_parallel_b'] as $id) {
            $ids[] = CloudMsgWebhook::query()->create([
                'provider_event_id' => $id,
                'payload' => ['type' => 'message.received', 'data' => [
                    'instance_id' => 'inst_test', 'type' => 'text',
                    'from' => '218910000001@c.us', 'chat_id' => '218910000001@c.us',
                    'text' => ['body' => 'نشر '.$event->id],
                ]],
            ])->id;
        }
        DB::disconnect();
        $children = [];
        foreach ($ids as $id) {
            $pid = pcntl_fork();
            $this->assertNotSame(-1, $pid);
            if ($pid === 0) {
                DB::purge();
                try {
                    app(ProcessCloudMsgWebhookAction::class)->execute($id);
                    DB::disconnect();
                    exit(0);
                } catch (Throwable) {
                    exit(1);
                }
            }
            $children[] = $pid;
        }
        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status), 'Concurrent publish failed.');
        }
        DB::purge();
        $this->assertSame(EventStatus::Published, $event->fresh()->status);
        $this->assertSame(1, WhatsAppMessage::query()->where('deduplication_key', 'invitation-published-'.$event->id)->count());
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'event.published')->count());
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->assertSame(2, CloudMsgWebhook::query()->where('status', 'processed')->count());
    }
}
