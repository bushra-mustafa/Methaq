<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Events\Pages\ManageEvents;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

/** Administrators edit customers' designs at any time and refresh live invitations; owners stop editing once published. */
final class AdminEventDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Admin design tests require the isolated MySQL test database.');
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

    public function test_an_administrator_with_two_factor_edits_a_published_design_and_each_save_is_audited(): void
    {
        $event = $this->publishedEvent();
        $admin = $this->administrator();
        $publishedBefore = $event->design->refresh()->only(['published_revision', 'published_scene_json', 'final_render_path']);

        $this->actingAs($admin)->withoutVite()->get($this->studio($event))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('App/Editor')
                ->where('mode', 'event-studio')
                ->where('owner.email', $event->user->email)
                ->where('revision', 1));

        $this->actingAs($admin)->patchJson($this->studio($event).'/design', ['document' => $this->documentWithBackground($event, '#4b172a'), 'expectedRevision' => 1])
            ->assertOk()
            ->assertJsonPath('revision', 2);

        $design = $event->design->refresh();
        $this->assertSame('#4b172a', $design->palette_json['values']['background']);
        $this->assertSame($publishedBefore, $design->only(['published_revision', 'published_scene_json', 'final_render_path']));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->getKey(), 'action' => 'event.design_admin_edited', 'subject_type' => 'event', 'subject_id' => $event->getKey()]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->getKey(), 'action' => 'event.draft_viewed', 'subject_id' => $event->getKey()]);
    }

    public function test_the_studio_refuses_admins_without_two_factor_customers_and_the_owner(): void
    {
        $event = $this->publishedEvent();
        $document = $this->documentWithBackground($event, '#112233');
        $noTwoFactor = User::factory()->create(['role' => UserRole::Admin]);

        foreach ([$noTwoFactor, User::factory()->create(), $event->user] as $user) {
            $this->actingAs($user)->get($this->studio($event))->assertForbidden();
            $this->actingAs($user)->get($this->studio($event).'/presentation')->assertForbidden();
            $this->actingAs($user)->patchJson($this->studio($event).'/design', ['document' => $document, 'expectedRevision' => 1])->assertForbidden();
            $this->actingAs($user)->post($this->studio($event).'/refresh', $this->render(1))->assertForbidden();
        }

        $this->assertSame(1, $event->design->refresh()->revision);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'event.design_admin_edited']);
    }

    public function test_a_stale_revision_in_the_studio_gets_a_conflict_with_the_current_snapshot(): void
    {
        $event = $this->publishedEvent();
        $admin = $this->administrator();
        $this->actingAs($admin)->patchJson($this->studio($event).'/design', ['document' => $this->documentWithBackground($event, '#112233'), 'expectedRevision' => 1])->assertOk();

        $this->actingAs($admin)->patchJson($this->studio($event).'/design', ['document' => $this->documentWithBackground($event, '#445566'), 'expectedRevision' => 1])
            ->assertConflict()
            ->assertJsonPath('snapshot.revision', 2)
            ->assertJsonPath('snapshot.document.palette.values.background', '#112233');

        $this->assertSame('#112233', $event->design->refresh()->palette_json['values']['background']);
    }

    public function test_the_owner_edits_her_draft_but_not_a_published_design(): void
    {
        $draft = $this->paidDraft();
        $this->actingAs($draft->user)->patchJson("/app/events/{$draft->getKey()}/design", ['document' => $this->documentWithBackground($draft, '#223344'), 'expectedRevision' => 1])->assertOk();

        $published = $this->publishedEvent();
        $this->actingAs($published->user)->patchJson("/app/events/{$published->getKey()}/design", ['document' => $this->documentWithBackground($published, '#223344'), 'expectedRevision' => 1])
            ->assertForbidden()
            ->assertJsonPath('message', 'دعوتك منشورة، فأُقفل تعديل التصميم. راسلي فريق ميثاق لطلب أي تعديل.');
        $this->assertSame(1, $published->design->refresh()->revision);

        foreach (['editor', 'presentation'] as $page) {
            $this->actingAs($published->user)->withoutVite()->get("/app/events/{$published->getKey()}/{$page}")
                ->assertOk()
                ->assertInertia(fn (Assert $inertia) => $inertia->component('App/Events/DesignLocked')->where('publish.state', 'published')->missing('document'));
        }
    }

    public function test_refreshing_replaces_the_published_copy_with_the_sanitized_draft_and_keeps_status_and_expiry(): void
    {
        $event = $this->publishedEvent(locked: [TemplateFeature::AnimatedBackdrop]);
        $admin = $this->administrator();
        $before = $event->refresh()->only(['status', 'published_at', 'expires_at']);
        $before = array_map(static fn (mixed $value): string => $value instanceof \BackedEnum ? $value->value : (string) $value, $before);
        $oldRender = substr($event->design->refresh()->final_render_path, strlen('storage/'));

        $document = $this->documentWithBackground($event, '#4b172a');
        $document['scene']['backdrop'] = ['preset' => 'henna-noir'];
        $this->actingAs($admin)->patchJson($this->studio($event).'/design', ['document' => $document, 'expectedRevision' => 1])->assertOk();

        $this->actingAs($admin)->post($this->studio($event).'/refresh', $this->render(2))
            ->assertRedirect($this->studio($event).'/presentation')
            ->assertSessionHas('status', 'invitation-refreshed');

        $design = $event->design->refresh();
        $this->assertSame(2, $design->published_revision);
        $this->assertSame('#4b172a', $design->published_palette_json['values']['background']);
        $this->assertSame('inherit', $design->published_scene_json['backdrop']['preset']);
        $this->assertSame('henna-noir', $design->scene_json['backdrop']['preset']);
        $this->assertSame($before, array_map(static fn (mixed $value): string => $value instanceof \BackedEnum ? $value->value : (string) $value, $event->refresh()->only(['status', 'published_at', 'expires_at'])));
        Storage::disk('public')->assertExists(substr($design->final_render_path, strlen('storage/')));
        Storage::disk('public')->assertMissing($oldRender);
        $metadata = json_decode((string) DB::table('audit_logs')->where('action', 'event.invitation_refreshed')->where('subject_id', $event->getKey())->value('metadata'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertEquals(['revision' => 2, 'previousRevision' => 1, 'removedFeatures' => ['animated_backdrop']], array_intersect_key($metadata, array_flip(['revision', 'previousRevision', 'removedFeatures'])));

        $this->actingAs($event->user)->withoutVite()->get("/app/events/{$event->getKey()}")
            ->assertInertia(fn (Assert $page) => $page->where('teamEdit.inPublishedVersion', true)->whereNot('teamEdit.refreshedAt', null)->where('event.designLocked', true));
    }

    public function test_refresh_is_refused_for_drafts_expired_or_suspended_invitations_and_stale_revisions(): void
    {
        $admin = $this->administrator();

        $draft = $this->paidDraft();
        $this->actingAs($admin)->post($this->studio($draft).'/refresh', $this->render(1))->assertSessionHasErrors(['publish' => 'الدعوة لم تُنشر بعد. تظهر تعديلاتك فيها عندما تنشرها صاحبتها.']);

        $expired = $this->publishedEvent();
        $this->travelTo($expired->expires_at->addMinute());
        $this->actingAs($admin)->post($this->studio($expired).'/refresh', $this->render(1))->assertSessionHasErrors(['publish' => 'انتهت صلاحية هذه الدعوة، فلا تُحدَّث نسختها المنشورة.']);
        $this->travelBack();

        $suspended = $this->publishedEvent();
        $suspended->forceFill(['suspended_at' => now(), 'suspension_reason' => 'مراجعة'])->save();
        $this->actingAs($admin)->post($this->studio($suspended).'/refresh', $this->render(1))->assertSessionHasErrors(['publish' => 'هذه الدعوة موقوفة، فلا تُحدَّث نسختها المنشورة أثناء الإيقاف.']);
        $this->actingAs($admin)->patchJson($this->studio($suspended).'/design', ['document' => $this->documentWithBackground($suspended, '#101010'), 'expectedRevision' => 1])->assertOk();

        $live = $this->publishedEvent();
        $this->actingAs($admin)->patchJson($this->studio($live).'/design', ['document' => $this->documentWithBackground($live, '#202020'), 'expectedRevision' => 1])->assertOk();
        $this->actingAs($admin)->post($this->studio($live).'/refresh', $this->render(1))->assertSessionHasErrors(['publish' => 'تغيّر التصميم منذ تحميل الصفحة. حدّثي الصفحة ثم حاولي مرة أخرى.']);

        $this->assertSame(1, $live->design->refresh()->published_revision);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'event.invitation_refreshed']);
    }

    public function test_the_events_table_offers_the_studio_link_to_administrators(): void
    {
        Filament::setCurrentPanel('admin');
        $event = $this->paidDraft();
        $this->actingAs($this->administrator());

        Livewire::test(ManageEvents::class)
            ->assertActionVisible(TestAction::make('design')->table($event))
            ->assertActionHasUrl(TestAction::make('design')->table($event), route('app.studio.events.show', $event));
    }

    /** @param list<TemplateFeature> $locked */
    private function publishedEvent(array $locked = []): Event
    {
        $event = $this->paidDraft($locked);
        $this->actingAs($event->user)->post("/app/events/{$event->getKey()}/publish", $this->render(1))->assertSessionHasNoErrors();
        auth()->logout();

        return $event->fresh(['user', 'design']);
    }

    /** @param list<TemplateFeature> $locked */
    private function paidDraft(array $locked = []): Event
    {
        static $sequence = 0;
        $sequence++;

        $template = Template::factory()->create(['price_minor' => 25_000, 'locked_features' => $locked]);
        $event = Event::factory()->create(['template_id' => $template->getKey(), 'subdomain' => "admin-design-{$sequence}"]);
        EventDesign::query()->forceCreate(['event_id' => $event->getKey(), ...app(BlankDesignFactory::class)->make()->toStorageColumns(), 'revision' => 1]);
        $event->load(['user', 'template', 'design']);

        Order::factory()->manualTransfer()->create([
            'event_id' => $event->getKey(),
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
            'transaction_id' => "manual-admin-{$sequence}",
            'entitlements' => app(ManualPaymentEligibility::class)->entitlements($event),
        ]);
        $event->forceFill(['is_paid' => true, 'paid_at' => now()])->save();

        return $event;
    }

    /** @return array<string, mixed> */
    private function documentWithBackground(Event $event, string $color): array
    {
        $design = $event->design()->sole();
        $palette = $design->palette_json;
        $palette['values']['background'] = $color;

        return ['schemaVersion' => $design->schema_version, 'canvas' => $design->design_json, 'palette' => $palette, 'scene' => $design->scene_json];
    }

    /** @return array<string, mixed> */
    private function render(int $revision): array
    {
        return ['render' => UploadedFile::fake()->image('card.png', 1080, 1920), 'expectedRevision' => $revision];
    }

    private function studio(Event $event): string
    {
        return "/app/studio/events/{$event->getKey()}";
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
