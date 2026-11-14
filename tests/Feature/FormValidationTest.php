<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Validation integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_rsvp_rejects_malformed_input_in_arabic_and_ignores_fields_it_does_not_own(): void
    {
        $event = $this->publishedEvent();
        $other = Event::factory()->create();
        $url = 'http://'.$event->subdomain.'.'.config('methaq.invitation_domain').'/rsvp';

        $this->post($url, [])->assertSessionHasErrors(['submission_token', 'guest_name', 'attending_status', 'companions_count']);

        $response = $this->from('/')->post($url, [
            'submission_token' => 'not-a-uuid',
            'guest_name' => "سارة\x07",
            'guest_phone' => 'اتصلي بي',
            'attending_status' => 'maybe-later',
            'companions_count' => 999,
            'notes' => str_repeat('م', 1001),
        ]);
        $response->assertSessionHasErrors(['submission_token', 'guest_name', 'guest_phone', 'attending_status', 'companions_count', 'notes']);
        $this->assertStringContainsString('الاسم', session('errors')->first('guest_name'));

        $this->post($url, [
            'submission_token' => 'f9e2c48a-dc0b-4a78-a441-6df84c7dc875',
            'guest_name' => '<script>alert(1)</script>',
            'attending_status' => 'attending',
            'companions_count' => 0,
            'event_id' => $other->getKey(),
        ])->assertRedirect();

        $this->assertDatabaseHas('rsvps', ['event_id' => $event->getKey(), 'guest_name' => '<script>alert(1)</script>']);
        $this->assertDatabaseMissing('rsvps', ['event_id' => $other->getKey()]);
    }

    public function test_registration_refuses_empty_weak_and_malicious_input_with_arabic_messages(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['name', 'email', 'password']);

        $response = $this->post('/register', ['name' => 'سارة', 'email' => 'not-an-email', 'phone' => 'drop table', 'password' => 'short', 'password_confirmation' => 'other']);
        $response->assertSessionHasErrors(['email', 'phone', 'password']);
        $this->assertStringContainsString('البريد الإلكتروني', session('errors')->first('email'));

        $this->post('/register', ['name' => 'س', 'email' => 'a@example.com', 'password' => 'Str0ng-Password!', 'password_confirmation' => 'Str0ng-Password!', 'role' => 'admin', 'status' => 'active'])->assertRedirect();
        $this->assertSame('customer', User::query()->where('email', 'a@example.com')->firstOrFail()->role->value);
    }

    public function test_a_password_needs_only_eight_characters_with_no_character_mix(): void
    {
        $this->post('/register', ['name' => 'سارة', 'email' => 'seven@example.com', 'password' => 'abcdefg', 'password_confirmation' => 'abcdefg'])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'seven@example.com']);

        $this->post('/register', ['name' => 'سارة', 'email' => 'eight@example.com', 'password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'eight@example.com']);
    }

    public function test_event_creation_refuses_forbidden_fields_bad_dates_and_foreign_ownership(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/app/events', [])->assertSessionHasErrors(['title', 'category', 'event_date', 'timezone']);

        $this->post('/app/events', [
            'title' => str_repeat('م', 201),
            'category' => 'weddings',
            'event_date' => '20/05/2027',
            'timezone' => 'Mars/Base',
            'user_id' => 999,
            'is_paid' => true,
            'status' => 'published',
            'subdomain' => 'hijack',
        ])->assertSessionHasErrors(['title', 'category', 'event_date', 'timezone', 'user_id', 'is_paid', 'status', 'subdomain']);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_receipt_upload_refuses_scripts_oversized_files_and_forged_payment_fields(): void
    {
        $event = Event::factory()->create();
        $this->actingAs(User::query()->findOrFail($event->user_id));
        $url = "/app/events/{$event->getKey()}/payment";

        $this->post($url, [])->assertSessionHasErrors('receipt');
        $this->post($url, ['receipt' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')])->assertSessionHasErrors('receipt');
        $this->post($url, ['receipt' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('receipt');
        $this->post($url, ['receipt' => UploadedFile::fake()->image('r.png'), 'amount' => 1, 'status' => 'completed'])->assertSessionHasErrors(['amount', 'status']);

        $this->assertDatabaseCount('orders', 0);
    }

    private function publishedEvent(): Event
    {
        $event = Event::factory()->create([
            'subdomain' => 'validation-check-2027',
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
