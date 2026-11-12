<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\ListFeaturedTemplatesAction;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\InvitationExpiry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class InvitationExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Invitation expiry integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_link_closes_at_midnight_tripoli_after_the_grace_days(): void
    {
        $expiry = app(InvitationExpiry::class);
        $eventDate = CarbonImmutable::parse('2027-08-14 20:00', 'Africa/Tripoli');

        $this->assertSame('2027-08-17 00:00', $expiry->expiresAt($eventDate)->setTimezone('Africa/Tripoli')->format('Y-m-d H:i'));

        config(['methaq.invitation_grace_days' => 0]);
        $this->assertSame('2027-08-15 00:00', $expiry->expiresAt($eventDate)->setTimezone('Africa/Tripoli')->format('Y-m-d H:i'));

        config(['methaq.invitation_grace_days' => 30]);
        $this->assertSame(InvitationExpiry::MAX_GRACE_DAYS, $expiry->graceDays());
    }

    public function test_guest_link_works_until_the_last_minute_then_shows_the_expired_page(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-08-16 23:59', 'Africa/Tripoli'));
        $event = $this->publishedEvent(CarbonImmutable::parse('2027-08-14 20:00', 'Africa/Tripoli'));

        $this->withoutVite()->get($this->guestUrl($event))->assertOk();

        $this->travelTo(CarbonImmutable::parse('2027-08-17 00:01', 'Africa/Tripoli'));
        $this->withoutVite()->get($this->guestUrl($event))
            ->assertStatus(410)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Guest/Expired', false)
                ->missing('event')
                ->missing('scene')
                ->missing('cardUrl'));
    }

    public function test_rsvp_is_refused_on_the_server_after_expiry(): void
    {
        $event = $this->publishedEvent(CarbonImmutable::now()->subDays(5));

        $this->post($this->guestUrl($event).'rsvp', [
            'submission_token' => 'f9e2c48a-dc0b-4a78-a441-6df84c7dc875',
            'guest_name' => 'سارة محمد',
            'attending_status' => 'attending',
            'companions_count' => 1,
        ])->assertStatus(410);

        $this->assertDatabaseCount('rsvps', 0);
    }

    public function test_suspended_or_expired_status_invitations_never_render(): void
    {
        $suspended = $this->publishedEvent(CarbonImmutable::now()->addDays(10), 'suspended-invite-2027');
        $suspended->forceFill(['suspended_at' => now(), 'suspension_reason' => 'مخالفة'])->save();
        $this->get($this->guestUrl($suspended))->assertNotFound();

        $closed = $this->publishedEvent(CarbonImmutable::now()->addDays(10), 'closed-invite-2027');
        $closed->forceFill(['status' => EventStatus::Expired])->save();
        $this->withoutVite()->get($this->guestUrl($closed))->assertStatus(410);
    }

    public function test_daily_command_expires_only_closed_links_and_keeps_designs_and_templates(): void
    {
        $template = Template::factory()->create(['is_active' => true, 'is_featured' => true]);
        $past = $this->publishedEvent(CarbonImmutable::now()->subDays(5), 'past-invite-2027', $template);
        $future = $this->publishedEvent(CarbonImmutable::now()->addDays(5), 'future-invite-2027', $template);

        $this->artisan('methaq:expire-invitations')->assertSuccessful();

        $this->assertSame(EventStatus::Expired, $past->refresh()->status);
        $this->assertSame(EventStatus::Published, $future->refresh()->status);
        $this->assertNotNull($past->design?->published_scene_json);
        $this->assertNotNull(Event::query()->find($past->getKey()));

        $template->refresh();
        $this->assertTrue($template->is_active);
        $this->assertTrue($template->is_featured);
        $this->assertContains((string) $template->getKey(), array_column(app(ListFeaturedTemplatesAction::class)->execute(), 'id'));
        $this->withoutVite()->get('/templates')->assertOk()->assertSee($template->name);
    }

    private function publishedEvent(CarbonImmutable $eventDate, string $subdomain = 'sara-and-omar-2027', ?Template $template = null): Event
    {
        $event = Event::factory()->create([
            'subdomain' => $subdomain,
            'template_id' => $template?->getKey(),
            'event_date' => $eventDate->setTimezone('UTC'),
            'expires_at' => app(InvitationExpiry::class)->expiresAt($eventDate),
            'status' => EventStatus::Published,
            'is_paid' => true,
            'paid_at' => now(),
            'published_at' => now(),
        ]);
        EventDesign::query()->forceCreate([
            'event_id' => $event->getKey(),
            'design_json' => ['width' => 1080, 'height' => 1920, 'layers' => []],
            'scene_json' => [],
            'palette_json' => [],
            'schema_version' => 1,
            'revision' => 1,
            'final_render_path' => 'renders/invitations/'.$event->getKey().'.png',
            'published_revision' => 1,
            'published_scene_json' => [],
            'published_palette_json' => [],
            'rendered_at' => now(),
        ]);

        return $event->load('design');
    }

    private function guestUrl(Event $event): string
    {
        return 'http://'.$event->subdomain.'.'.config('methaq.invitation_domain').'/';
    }
}
