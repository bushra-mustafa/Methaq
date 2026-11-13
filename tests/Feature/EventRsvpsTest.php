<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Enums\AttendingStatus;
use App\Domains\RSVP\Models\Rsvp;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class EventRsvpsTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('RSVP list tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_the_owner_sees_the_totals_and_every_reply(): void
    {
        [$owner, $event] = $this->eventWithReplies();

        $this->actingAs($owner)->withoutVite()->get("/app/events/{$event->getKey()}/rsvps")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('App/Events/Rsvps', false)
                ->where('summary.responses', 4)
                ->where('summary.attending', 2)
                ->where('summary.companions', 3)
                ->where('summary.headcount', 5)
                ->where('summary.declined', 1)
                ->where('summary.maybe', 1)
                ->has('rsvps', 4)
                ->where('rsvps.0.guestPhone', fn ($phone): bool => is_string($phone) || $phone === null)
                ->where('truncated', false));
    }

    public function test_replies_belong_to_the_owner_alone_even_an_administrator_cannot_read_them(): void
    {
        [, $event] = $this->eventWithReplies();
        $url = "/app/events/{$event->getKey()}/rsvps";

        $this->get($url)->assertRedirect();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get("{$url}/export")->assertForbidden();
    }

    public function test_an_event_without_replies_shows_an_empty_list(): void
    {
        $owner = User::factory()->create();
        $event = $this->event($owner);

        $this->actingAs($owner)->withoutVite()->get("/app/events/{$event->getKey()}/rsvps")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('summary.responses', 0)->where('summary.headcount', 0)->has('rsvps', 0));
    }

    public function test_the_event_page_carries_the_summary(): void
    {
        [$owner, $event] = $this->eventWithReplies();

        $this->actingAs($owner)->withoutVite()->get("/app/events/{$event->getKey()}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rsvpSummary.headcount', 5)->where('rsvpSummary.responses', 4));
    }

    public function test_the_csv_holds_this_events_replies_with_safe_cells(): void
    {
        [$owner, $event] = $this->eventWithReplies();
        $other = $this->event(User::factory()->create(), 'another-event');
        Rsvp::factory()->create(['event_id' => $other->getKey(), 'guest_name' => 'ضيف مناسبة أخرى']);
        Rsvp::factory()->create(['event_id' => $event->getKey(), 'guest_name' => '=HYPERLINK("http://evil.test")', 'attending_status' => AttendingStatus::Attending->value, 'companions_count' => 0]);

        $response = $this->actingAs($owner)->get("/app/events/{$event->getKey()}/rsvps/export")->assertOk();
        $body = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('الاسم', $body);
        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringNotContainsString('ضيف مناسبة أخرى', $body);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    /** @return array{User, Event} */
    private function eventWithReplies(): array
    {
        $owner = User::factory()->create();
        $event = $this->event($owner);
        Rsvp::factory()->create(['event_id' => $event->getKey(), 'attending_status' => 'attending', 'companions_count' => 2]);
        Rsvp::factory()->create(['event_id' => $event->getKey(), 'attending_status' => 'attending', 'companions_count' => 1]);
        Rsvp::factory()->create(['event_id' => $event->getKey(), 'attending_status' => 'declined', 'companions_count' => 0]);
        Rsvp::factory()->create(['event_id' => $event->getKey(), 'attending_status' => 'maybe', 'companions_count' => 0]);

        return [$owner, $event];
    }

    private function event(User $owner, string $subdomain = 'rsvp-event'): Event
    {
        $event = Event::factory()->create(['user_id' => $owner->getKey(), 'subdomain' => $subdomain]);
        EventDesign::factory()->create(['event_id' => $event->getKey()]);

        return $event;
    }
}
