<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Actions\ChangeSupportThreadStatusAction;
use App\Domains\Users\Actions\DeleteContactChannelAction;
use App\Domains\Users\Actions\SaveContactChannelAction;
use App\Domains\Users\Enums\ContactIcon;
use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Enums\SupportTopic;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\ContactChannel;
use App\Domains\Users\Models\SupportMessage;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Filament\Resources\ContactChannels\Pages\ManageContactChannels;
use App\Filament\Resources\SupportThreads\Pages\ManageSupportThreads;
use App\Mail\SupportAcknowledgementMail;
use App\Mail\SupportMessageReceivedMail;
use Database\Seeders\ContactChannelSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class SupportMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Support tests require the isolated MySQL test database.');
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

    public function test_a_customer_opens_a_conversation_and_sees_it_in_her_list(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post('/app/support', ['topic' => 'custom_design', 'subject' => 'تصميم لحفل خطوبة', 'body' => 'أريد قالباً بألوان الزيتون.'])
            ->assertRedirect();

        $thread = SupportThread::query()->firstOrFail();
        $this->assertSame(SupportTopic::CustomDesign, $thread->topic);
        $this->assertTrue($thread->admin_unread);
        $this->assertSame(1, $thread->messages()->count());
        $this->actingAs($customer)->get('/app/support')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('App/Support/Index')
            ->has('threads', 1)
            ->where('threads.0.subject', 'تصميم لحفل خطوبة'));
    }

    public function test_invalid_messages_and_unknown_topics_are_refused(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post('/app/support', ['topic' => 'spam', 'subject' => '', 'body' => str_repeat('a', 3001)])
            ->assertSessionHasErrors(['topic', 'subject', 'body']);
        $this->assertDatabaseCount('support_threads', 0);
    }

    public function test_guests_cannot_write_and_the_open_conversation_limit_holds(): void
    {
        $this->post('/app/support', ['topic' => 'problem', 'subject' => 'x', 'body' => 'y'])->assertRedirect('/login');

        config(['methaq.limits.max_open_support_threads' => 1]);
        $customer = User::factory()->create();
        SupportThread::factory()->create(['user_id' => $customer->getKey()]);

        $this->actingAs($customer)->post('/app/support', ['topic' => 'problem', 'subject' => 'ثانية', 'body' => 'نص'])
            ->assertSessionHasErrors('body');
        $this->assertDatabaseCount('support_threads', 1);
    }

    public function test_another_customer_cannot_read_or_answer_a_conversation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $thread = SupportThread::factory()->create(['user_id' => $owner->getKey()]);

        $this->actingAs($other)->get("/app/support/{$thread->getKey()}")->assertNotFound();
        $this->actingAs($other)->post("/app/support/{$thread->getKey()}/reply", ['body' => 'تطفل'])->assertNotFound();
        $this->assertDatabaseCount('support_messages', 0);
    }

    public function test_the_administrator_reply_reaches_the_customer_and_unread_marks_follow(): void
    {
        $admin = $this->administrator();
        $customer = User::factory()->create();
        $thread = SupportThread::factory()->create(['user_id' => $customer->getKey()]);
        SupportMessage::factory()->create(['support_thread_id' => $thread->getKey(), 'author_id' => $customer->getKey(), 'body' => 'عندي مشكلة']);
        $this->actingAs($admin);

        Livewire::test(ManageSupportThreads::class)
            ->assertCanSeeTableRecords([$thread])
            ->callAction(TestAction::make('conversation')->table($thread), ['body' => 'نحن نتابع مشكلتك'])
            ->assertNotified('أُرسل الرد');

        $thread->refresh();
        $this->assertFalse($thread->admin_unread);
        $this->assertTrue($thread->customer_unread);
        $this->assertTrue($thread->messages()->reorder()->latest('id')->first()->from_admin);

        $this->actingAs($customer)->get("/app/support/{$thread->getKey()}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('App/Support/Show')
            ->where('messages.1.fromTeam', true)
            ->where('messages.1.body', 'نحن نتابع مشكلتك'));
        $this->assertFalse($thread->fresh()->customer_unread);

        $this->actingAs($customer)->post("/app/support/{$thread->getKey()}/reply", ['body' => 'شكراً'])->assertRedirect();
        $this->assertFalse($thread->messages()->reorder()->latest('id')->first()->from_admin);
        $this->assertTrue($thread->fresh()->admin_unread);
    }

    public function test_a_closed_conversation_takes_no_replies_until_it_is_reopened_and_changes_are_audited(): void
    {
        $admin = $this->administrator();
        $customer = User::factory()->create();
        $thread = SupportThread::factory()->create(['user_id' => $customer->getKey()]);
        $this->actingAs($admin);

        Livewire::test(ManageSupportThreads::class)->callAction(TestAction::make('close')->table($thread));
        $this->assertSame(SupportThreadStatus::Closed, $thread->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'support_thread.closed', 'subject_id' => $thread->getKey()]);

        $this->actingAs($customer)->post("/app/support/{$thread->getKey()}/reply", ['body' => 'هل من جديد؟'])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('support_messages', 0);

        $this->actingAs($admin);
        Livewire::test(ManageSupportThreads::class)
            ->filterTable('status', SupportThreadStatus::Closed->value)
            ->callAction(TestAction::make('reopen')->table($thread));
        $this->assertSame(SupportThreadStatus::Open, $thread->fresh()->status);
    }

    public function test_customers_cannot_open_the_admin_inbox_or_change_a_conversation_status(): void
    {
        $customer = User::factory()->create();
        $thread = SupportThread::factory()->create(['user_id' => $customer->getKey()]);

        $this->actingAs($customer)->get('/admin/support-threads')->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(ChangeSupportThreadStatusAction::class)->execute($customer, (int) $thread->getKey(), SupportThreadStatus::Closed);
    }

    public function test_a_new_message_notifies_the_official_inbox_with_the_title_only_and_acknowledges_the_customer(): void
    {
        Mail::fake();
        config(['methaq.support.inbox' => 'inbox@example.test']);
        $customer = User::factory()->create(['email' => 'client@example.test']);

        $this->actingAs($customer)->post('/app/support', ['topic' => 'problem', 'subject' => "عنوان\r\nBcc: x@evil.test", 'body' => 'نص سري لا يخرج من الموقع'])->assertRedirect();

        Mail::assertSent(SupportMessageReceivedMail::class, function (SupportMessageReceivedMail $mail): bool {
            return $mail->hasTo('inbox@example.test')
                && ! $mail->isReply
                && ! str_contains($mail->title, "\n")
                && ! str_contains($mail->render(), 'نص سري');
        });
        Mail::assertSent(SupportAcknowledgementMail::class, fn (SupportAcknowledgementMail $mail): bool => $mail->hasTo('client@example.test'));
    }

    public function test_a_customer_reply_notifies_the_team_without_a_second_acknowledgement_and_admin_replies_send_nothing_to_the_team(): void
    {
        Mail::fake();
        config(['methaq.support.inbox' => 'inbox@example.test']);
        $customer = User::factory()->create();
        $thread = SupportThread::factory()->create(['user_id' => $customer->getKey(), 'admin_unread' => false]);

        $this->actingAs($customer)->post("/app/support/{$thread->getKey()}/reply", ['body' => 'إضافة'])->assertRedirect();
        Mail::assertSent(SupportMessageReceivedMail::class, fn (SupportMessageReceivedMail $mail): bool => $mail->isReply);
        Mail::assertNotSent(SupportAcknowledgementMail::class);

        // While that message is still unread the team is not told again, so one account cannot flood the inbox.
        $this->actingAs($customer)->post("/app/support/{$thread->getKey()}/reply", ['body' => 'وأخرى'])->assertRedirect();
        Mail::assertSentCount(1);

        $this->actingAs($this->administrator());
        Livewire::test(ManageSupportThreads::class)->callAction(TestAction::make('conversation')->table($thread), ['body' => 'رد الفريق']);
        Mail::assertSentCount(1);
    }

    public function test_an_empty_inbox_setting_turns_the_team_notice_off_and_a_mail_failure_never_breaks_the_request(): void
    {
        Mail::fake();
        config(['methaq.support.inbox' => '']);
        $customer = User::factory()->create();

        $this->actingAs($customer)->post('/app/support', ['topic' => 'problem', 'subject' => 'عنوان', 'body' => 'نص'])->assertRedirect();
        Mail::assertNotSent(SupportMessageReceivedMail::class);
        Mail::assertSent(SupportAcknowledgementMail::class);

        config(['methaq.support.inbox' => 'inbox@example.test']);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $this->actingAs($customer)->post('/app/support', ['topic' => 'problem', 'subject' => 'ثانية', 'body' => 'نص'])->assertRedirect();
        $this->assertDatabaseCount('support_threads', 2);
    }

    public function test_the_contact_page_lists_only_active_channels_and_the_seeder_adds_the_official_email_once(): void
    {
        $this->seed(ContactChannelSeeder::class);
        $this->seed(ContactChannelSeeder::class);
        ContactChannel::factory()->create(['name' => 'قناة مخفية', 'is_active' => false]);
        ContactChannel::factory()->create(['name' => 'إنستغرام ميثاق', 'url' => 'https://instagram.com/methaq', 'icon' => ContactIcon::Instagram, 'sort_order' => 1]);

        $this->assertSame(1, ContactChannel::query()->where('url', 'mailto:info@methq.ly')->count());
        $this->get('/contact')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Web/Contact')
            ->has('channels', 2)
            ->where('channels.0.url', 'mailto:info@methq.ly')
            ->where('channels.1.name', 'إنستغرام ميثاق'));
    }

    public function test_only_https_mailto_and_tel_links_can_be_saved_and_changes_are_audited(): void
    {
        $admin = $this->administrator();
        $save = app(SaveContactChannelAction::class);
        $data = ['name' => 'واتساب', 'url' => 'tel:+218910000000', 'icon' => ContactIcon::Phone, 'is_active' => true, 'sort_order' => 2];

        $channel = $save->execute($admin, $data);
        $this->assertDatabaseHas('contact_channels', ['id' => $channel->getKey(), 'url' => 'tel:+218910000000']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'contact_channel.saved', 'subject_id' => $channel->getKey()]);

        foreach (['javascript:alert(1)', 'http://insecure.example', 'data:text/html;base64,AA==', 'https://a b.example'] as $url) {
            try {
                $save->execute($admin, [...$data, 'url' => $url]);
                $this->fail("{$url} was accepted");
            } catch (ValidationException) {
                $this->assertDatabaseMissing('contact_channels', ['url' => $url]);
            }
        }

        app(DeleteContactChannelAction::class)->execute($admin, (int) $channel->getKey());
        $this->assertDatabaseMissing('contact_channels', ['id' => $channel->getKey()]);
        $this->assertSame(1, AuditLog::query()->where('action', 'contact_channel.deleted')->count());
    }

    public function test_a_customer_cannot_manage_contact_channels(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/contact-channels')->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(SaveContactChannelAction::class)->execute($customer, ['name' => 'x', 'url' => 'https://example.com', 'icon' => ContactIcon::Globe, 'is_active' => true, 'sort_order' => 0]);
    }

    public function test_the_admin_can_add_a_channel_from_the_panel(): void
    {
        $this->actingAs($this->administrator());

        Livewire::test(ManageContactChannels::class)
            ->callAction('create', ['name' => 'تيليغرام', 'url' => 'https://t.me/methaq', 'icon' => ContactIcon::Telegram->value, 'is_active' => true, 'sort_order' => 3])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contact_channels', ['name' => 'تيليغرام', 'icon' => 'telegram']);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
