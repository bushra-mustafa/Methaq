<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Models\Rsvp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class PublicInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Public invitation integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_only_a_live_published_invitation_is_available_on_its_subdomain(): void
    {
        $event = $this->publishedEvent();

        $this->withServerVariables(['HTTP_HOST' => $event->subdomain.'.'.config('methaq.invitation_domain')])
            ->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Guest/Show', false)
                ->where('event.title', $event->title)
                ->where('cardUrl', '/renders/invitations/'.$event->getKey().'.png')
                ->missing('document'));

        $draft = Event::factory()->create(['subdomain' => 'still-a-draft']);
        EventDesign::factory()->create(['event_id' => $draft->getKey()]);
        $this->withServerVariables(['HTTP_HOST' => $draft->subdomain.'.'.config('methaq.invitation_domain')])
            ->get('/')
            ->assertNotFound();
    }

    public function test_guest_can_submit_or_correct_their_rsvp_without_an_account(): void
    {
        $event = $this->publishedEvent();
        $host = $event->subdomain.'.'.config('methaq.invitation_domain');
        $token = 'f9e2c48a-dc0b-4a78-a441-6df84c7dc875';

        $payload = [
            'submission_token' => $token,
            'guest_name' => 'سارة محمد',
            'guest_phone' => '0910000000',
            'attending_status' => 'attending',
            'companions_count' => 2,
            'notes' => 'سعيدة بمشاركتكم.',
        ];

        $this->withServerVariables(['HTTP_HOST' => $host])->post('/rsvp', $payload)->assertRedirect();
        $this->assertDatabaseHas('rsvps', ['event_id' => $event->getKey(), 'submission_token' => $token, 'companions_count' => 2]);

        $this->withServerVariables(['HTTP_HOST' => $host])->post('/rsvp', array_replace($payload, [
            'attending_status' => 'declined',
            'companions_count' => 4,
        ]))->assertRedirect();

        $this->assertSame(1, Rsvp::query()->where('event_id', $event->getKey())->count());
        $this->assertDatabaseHas('rsvps', ['event_id' => $event->getKey(), 'submission_token' => $token, 'attending_status' => 'declined', 'companions_count' => 0]);
    }

    private function publishedEvent(): Event
    {
        $event = Event::factory()->create([
            'subdomain' => 'sara-and-omar-2027',
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

        return $event;
    }
}
