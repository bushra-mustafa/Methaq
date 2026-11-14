<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SpamProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Spam protection tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_sign_up_is_limited_per_address_and_bots_that_fill_the_honeypot_are_refused(): void
    {
        $this->post('/register', ['name' => 'بوت', 'email' => 'bot@example.com', 'password' => 'Str0ng-Password!', 'password_confirmation' => 'Str0ng-Password!', 'website' => 'http://spam.example'])
            ->assertSessionHasErrors('website');
        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);

        foreach (range(1, 4) as $attempt) {
            $this->post('/register', [])->assertSessionHasErrors('name');
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_password_reset_requests_are_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/forgot-password', ['email' => "x{$attempt}@example.com"]);
        }

        $this->post('/forgot-password', ['email' => 'late@example.com'])->assertStatus(429);
    }

    public function test_guest_replies_allow_a_family_but_stop_a_flood_and_refuse_the_honeypot(): void
    {
        $event = $this->publishedEvent();
        $url = 'http://'.$event->subdomain.'.'.config('methaq.invitation_domain').'/rsvp';
        $payload = fn (string $token): array => ['submission_token' => $token, 'guest_name' => 'ضيف', 'attending_status' => 'attending', 'companions_count' => 0];

        $this->post($url, [...$payload('f9e2c48a-dc0b-4a78-a441-6df84c7dc801'), 'website' => 'spam'])->assertSessionHasErrors('website');
        $this->assertDatabaseCount('rsvps', 0);

        foreach (range(2, 20) as $number) {
            $this->post($url, $payload(sprintf('f9e2c48a-dc0b-4a78-a441-6df84c7dc8%02d', $number)))->assertRedirect();
        }

        $this->post($url, $payload('f9e2c48a-dc0b-4a78-a441-6df84c7dc899'))->assertStatus(429);
        $this->assertDatabaseCount('rsvps', 19);
    }

    private function publishedEvent(): Event
    {
        $event = Event::factory()->create(['subdomain' => 'spam-check-2027', 'status' => EventStatus::Published, 'is_paid' => true, 'paid_at' => now(), 'published_at' => now()]);
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
