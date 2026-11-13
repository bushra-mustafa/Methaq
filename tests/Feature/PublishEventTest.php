<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\DTOs\ColorValueData;
use App\Domains\Editor\DTOs\EffectData;
use App\Domains\Editor\Enums\EffectId;
use App\Domains\Editor\Enums\PaletteRole;
use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\InvitationExpiry;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\RSVP\Models\Rsvp;
use App\Domains\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class PublishEventTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Publishing integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_owner_publishes_a_paid_draft_and_the_guest_page_serves_the_stored_render(): void
    {
        $event = $this->paidDraft();

        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload())
            ->assertRedirect("/app/events/{$event->getKey()}")
            ->assertSessionHas('status', 'event-published');

        $event->refresh();
        $design = $event->design->refresh();
        $this->assertSame(EventStatus::Published, $event->status);
        $this->assertNotNull($event->published_at);
        $this->assertEquals(app(InvitationExpiry::class)->expiresAt(CarbonImmutable::instance($event->event_date)), $event->expires_at);
        $this->assertSame(1, $design->published_revision);
        $this->assertNotNull($design->rendered_at);
        $this->assertMatchesRegularExpression('#^storage/renders/invitations/'.$event->getKey().'/[0-9a-f-]{36}\.png$#', $design->final_render_path);
        Storage::disk('public')->assertExists(substr($design->final_render_path, strlen('storage/')));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $event->user_id, 'action' => 'event.published', 'subject_type' => 'event', 'subject_id' => $event->getKey()]);

        $this->withoutVite()->get($this->guestUrl($event))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Guest/Show', false)
                ->where('cardUrl', '/'.$design->final_render_path)
                ->where('rsvpEnabled', true));
    }

    public function test_publishing_is_refused_without_an_approved_payment(): void
    {
        $unpaid = $this->paidDraft();
        $unpaid->orders()->delete();
        $unpaid->forceFill(['is_paid' => false, 'paid_at' => null])->save();

        $pending = $this->paidDraft();
        $pending->orders()->update(['status' => OrderStatus::Pending->value, 'completed_at' => null, 'transaction_id' => null]);
        $pending->forceFill(['is_paid' => false, 'paid_at' => null])->save();

        foreach ([$unpaid, $pending] as $event) {
            $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload())->assertSessionHasErrors(['publish' => 'لا يمكن النشر قبل اعتماد الدفع.']);
            $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_only_the_owner_can_publish_and_guests_are_sent_to_login(): void
    {
        $event = $this->paidDraft();

        $this->post($this->publishUrl($event), $this->payload())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post($this->publishUrl($event), $this->payload())->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->post($this->publishUrl($event), $this->payload())->assertForbidden();

        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
    }

    public function test_a_past_event_a_suspended_event_and_a_second_publish_are_refused(): void
    {
        $past = $this->paidDraft();
        $past->forceFill(['event_date' => now()->subDay(), 'expires_at' => now()->addDay()])->save();
        $this->actingAs($past->user)->post($this->publishUrl($past), $this->payload())->assertSessionHasErrors(['publish' => 'موعد المناسبة مضى، فلا يمكن نشرها.']);

        $suspended = $this->paidDraft();
        $suspended->forceFill(['suspended_at' => now(), 'suspension_reason' => 'مخالفة'])->save();
        $this->actingAs($suspended->user)->post($this->publishUrl($suspended), $this->payload())->assertSessionHasErrors('publish');

        $event = $this->paidDraft();
        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload())->assertSessionHasNoErrors();
        $firstPath = $event->design->refresh()->final_render_path;
        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload())->assertSessionHasErrors(['publish' => 'المناسبة منشورة بالفعل.']);

        $this->assertSame($firstPath, $event->design->refresh()->final_render_path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_a_stale_design_revision_is_refused(): void
    {
        $event = $this->paidDraft();
        $event->design->forceFill(['revision' => 3])->save();

        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload(revision: 1))->assertSessionHasErrors('publish');

        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_render_must_be_a_real_1080_by_1920_png_and_metadata_is_stripped(): void
    {
        $event = $this->paidDraft();
        $this->actingAs($event->user);

        $disguised = tempnam(sys_get_temp_dir(), 'render');
        file_put_contents($disguised, '<?php echo 1;');
        $this->post($this->publishUrl($event), ['render' => new UploadedFile($disguised, 'card.png', 'image/png', null, true), 'expectedRevision' => 1])->assertSessionHasErrors('render');
        $this->post($this->publishUrl($event), ['render' => UploadedFile::fake()->image('card.png', 500, 500), 'expectedRevision' => 1])->assertSessionHasErrors('render');
        $this->post($this->publishUrl($event), ['render' => UploadedFile::fake()->image('card.jpg', 1080, 1920), 'expectedRevision' => 1])->assertSessionHasErrors('render');
        $this->post($this->publishUrl($event), [...$this->payload(), 'status' => 'published', 'is_paid' => 1])->assertSessionHasErrors(['status', 'is_paid']);

        $this->assertSame(EventStatus::Draft, $event->refresh()->status);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->post($this->publishUrl($event), $this->payload())->assertSessionHasNoErrors();
        $stored = Storage::disk('public')->get(substr($event->design->refresh()->final_render_path, strlen('storage/')));
        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($stored)[2]);
        $this->assertStringNotContainsString('eXIf', $stored);
    }

    public function test_the_published_snapshot_stays_fixed_when_the_draft_changes_afterwards(): void
    {
        $event = $this->paidDraft();
        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload());
        $published = $event->design->refresh()->published_scene_json;

        $event->design->forceFill(['scene_json' => [...$event->design->scene_json, 'backdrop' => ['preset' => 'emerald-silk']], 'revision' => 2])->save();

        $design = $event->design->refresh();
        $this->assertSame($published, $design->published_scene_json);
        $this->assertSame(1, $design->published_revision);
        $this->assertSame('emerald-silk', $design->scene_json['backdrop']['preset']);
    }

    public function test_locked_music_and_animated_backdrop_are_removed_from_the_published_scene_only(): void
    {
        $event = $this->paidDraft(locked: [TemplateFeature::Music, TemplateFeature::AnimatedBackdrop], scene: $this->animatedScene());
        $draft = $event->design->scene_json;

        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload())->assertSessionHasNoErrors();

        $design = $event->design->refresh();
        $published = $design->published_scene_json;
        $this->assertFalse($published['audio']['enabled']);
        $this->assertNull($published['audio']['asset']);
        $this->assertSame('inherit', $published['backdrop']['preset']);
        $this->assertFalse($published['effects'][0]['enabled']);
        $this->assertSame($draft, $design->scene_json);
        $this->assertSame(['music', 'animated_backdrop'], $this->auditMetadata($event)['removedFeatures']);
    }

    public function test_unlocked_features_are_published_as_designed(): void
    {
        $event = $this->paidDraft(scene: $this->animatedScene());

        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload())->assertSessionHasNoErrors();

        $published = $event->design->refresh()->published_scene_json;
        $this->assertTrue($published['audio']['enabled']);
        $this->assertSame('henna-noir', $published['backdrop']['preset']);
        $this->assertTrue($published['effects'][0]['enabled']);
        $this->assertSame([], $this->auditMetadata($event)['removedFeatures']);
    }

    public function test_the_presentation_page_reports_what_publishing_will_drop(): void
    {
        $ready = $this->paidDraft(locked: [TemplateFeature::Music], scene: $this->animatedScene());
        $this->actingAs($ready->user)->withoutVite()->get("/app/events/{$ready->getKey()}/presentation")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('publish.state', 'ready')
                ->where('publish.canPublish', true)
                ->where('publish.willRemove', ['الموسيقى']));

        $unpaid = $this->paidDraft();
        $unpaid->orders()->delete();
        $unpaid->forceFill(['is_paid' => false, 'paid_at' => null])->save();
        $this->actingAs($unpaid->user)->withoutVite()->get("/app/events/{$unpaid->getKey()}/presentation")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('publish.state', 'blocked')
                ->where('publish.canPublish', false)
                ->where('publish.blocker', 'لا يمكن النشر قبل اعتماد الدفع.'));
    }

    public function test_locked_rsvp_hides_the_form_and_the_server_refuses_responses(): void
    {
        $event = $this->paidDraft(locked: [TemplateFeature::Rsvp]);
        $this->actingAs($event->user)->post($this->publishUrl($event), $this->payload())->assertSessionHasNoErrors();
        auth()->logout();

        $this->withoutVite()->get($this->guestUrl($event))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rsvpEnabled', false));

        $this->post($this->guestUrl($event).'rsvp', [
            'submission_token' => 'f9e2c48a-dc0b-4a78-a441-6df84c7dc875',
            'guest_name' => 'سارة محمد',
            'attending_status' => 'attending',
            'companions_count' => 1,
        ])->assertForbidden();

        $this->assertSame(0, Rsvp::query()->count());
    }

    /**
     * @param  list<TemplateFeature>  $locked
     * @param  array<string, mixed>|null  $scene
     */
    private function paidDraft(array $locked = [], ?array $scene = null): Event
    {
        static $sequence = 0;
        $sequence++;

        $template = Template::factory()->create(['price_minor' => 25_000, 'locked_features' => $locked]);
        $event = Event::factory()->create(['template_id' => $template->getKey(), 'subdomain' => "publish-test-{$sequence}"]);
        $columns = app(BlankDesignFactory::class)->make()->toStorageColumns();
        EventDesign::query()->forceCreate([
            'event_id' => $event->getKey(),
            ...$columns,
            'scene_json' => $scene ?? $columns['scene_json'],
            'revision' => 1,
        ]);
        $event = $event->load(['user', 'template', 'design']);

        Order::factory()->manualTransfer()->create([
            'event_id' => $event->getKey(),
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
            'transaction_id' => "manual-test-{$sequence}",
            'entitlements' => app(ManualPaymentEligibility::class)->entitlements($event),
        ]);
        $event->forceFill(['is_paid' => true, 'paid_at' => now()])->save();

        return $event;
    }

    /** @return array<string, mixed> */
    private function animatedScene(): array
    {
        $scene = app(BlankDesignFactory::class)->make()->toStorageColumns()['scene_json'];
        $scene['audio'] = ['enabled' => true, 'asset' => ['assetId' => '7', 'version' => 1], 'volume' => 0.8];
        $scene['backdrop'] = ['preset' => 'henna-noir'];
        $scene['effects'] = [(new EffectData(EffectId::Sparkle, 1, true, ColorValueData::palette(PaletteRole::Effect), 0.5, 1.0))->toArray()];

        return $scene;
    }

    /** @return array<string, mixed> */
    private function payload(int $revision = 1): array
    {
        return ['render' => UploadedFile::fake()->image('card.png', 1080, 1920), 'expectedRevision' => $revision];
    }

    private function publishUrl(Event $event): string
    {
        return "/app/events/{$event->getKey()}/publish";
    }

    private function guestUrl(Event $event): string
    {
        return 'http://'.$event->subdomain.'.'.config('methaq.invitation_domain').'/';
    }

    /** @return array<string, mixed> */
    private function auditMetadata(Event $event): array
    {
        $json = DB::table('audit_logs')->where('action', 'event.published')->where('subject_id', $event->getKey())->value('metadata');

        return json_decode((string) $json, true, flags: JSON_THROW_ON_ERROR);
    }
}
