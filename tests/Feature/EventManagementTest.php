<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Event integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_event_routes_require_authentication(): void
    {
        $this->get('/app/events/create')->assertRedirect(route('login'));
        $this->post('/app/events', [])->assertRedirect(route('login'));
    }

    public function test_creation_page_selects_an_active_template_from_its_slug(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->withoutVite()->get('/app/events/create?template=powder-gold')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('App/Events/Create', false)
                ->has('templates', 4)
                ->has('categories', 4)
                ->where('selectedTemplateSlug', 'powder-gold'));
    }

    public function test_event_created_from_template_copies_design_and_uses_calendar_expiry(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 00:00:00 UTC'));
        $this->seed(TemplateLibrarySeeder::class);
        $user = User::factory()->create();
        $template = Template::query()->where('slug', 'powder-gold')->sole();

        $response = $this->actingAs($user)->post('/app/events', [
            'title' => 'حفل زفاف سارة وعمر',
            'category' => EventCategory::Graduation->value,
            'template_id' => $template->getKey(),
            'event_date' => '2027-03-26T12:00',
            'timezone' => 'Europe/London',
        ]);

        $event = Event::query()->with('design')->sole();
        $response->assertRedirect(route('app.events.show', $event));
        $this->assertSame($user->getKey(), $event->user_id);
        $this->assertSame(EventCategory::Wedding, $event->category);
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertFalse($event->is_paid);
        $this->assertNull($event->paid_at);
        $this->assertSame('2027-03-26 12:00', $event->event_date->setTimezone('Europe/London')->format('Y-m-d H:i'));
        $this->assertSame('2027-04-05 12:00', $event->expires_at->setTimezone('Europe/London')->format('Y-m-d H:i'));
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*-2027$/', $event->subdomain);
        $this->assertSame($template->default_design_json, $event->design->design_json);
        $this->assertSame($template->default_scene_json, $event->design->scene_json);
        $this->assertSame($template->default_palette_json, $event->design->palette_json);
        $this->assertSame(1, $event->design->revision);
    }

    public function test_blank_event_receives_a_safe_empty_design(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/app/events', $this->validPayload([
            'title' => 'حفل تخرج ليان',
            'category' => EventCategory::Graduation->value,
        ]));

        $event = Event::query()->with('design')->sole();
        $response->assertRedirect(route('app.events.show', $event));
        $this->assertNull($event->template_id);
        $this->assertSame(EventCategory::Graduation, $event->category);
        $this->assertSame([], $event->design->design_json['layers']);
        $this->assertSame(1080, $event->design->design_json['width']);
        $this->assertSame(1920, $event->design->design_json['height']);
        $this->assertSame('direct', $event->design->scene_json['opening']['type']);
        $this->assertSame(1, $event->design->schema_version);
    }

    public function test_duplicate_human_slug_is_retried_with_a_unique_suffix(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload(['title' => 'Wedding Night']);

        $this->actingAs($user)->post('/app/events', $payload)->assertRedirect();
        $this->actingAs($user)->post('/app/events', $payload)->assertRedirect();

        $slugs = Event::query()->orderBy('id')->pluck('subdomain')->all();
        $this->assertSame('wedding-night-2027', $slugs[0]);
        $this->assertMatchesRegularExpression('/^wedding-night-2027-[a-z0-9]{6}$/', $slugs[1]);
        $this->assertNotSame($slugs[0], $slugs[1]);
    }

    public function test_server_owned_fields_are_prohibited(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post('/app/events', $this->validPayload([
            'user_id' => $other->getKey(),
            'subdomain' => 'forced-domain',
            'expires_at' => '2030-01-01T10:00',
            'status' => 'published',
            'is_paid' => true,
            'paid_at' => '2026-01-01T00:00',
            'published_at' => '2026-01-01T00:00',
        ]))->assertSessionHasErrors([
            'user_id', 'subdomain', 'expires_at', 'status', 'is_paid', 'paid_at', 'published_at',
        ]);

        $this->assertDatabaseCount('events', 0);
        $this->assertDatabaseCount('event_designs', 0);
    }

    public function test_past_date_and_inactive_template_are_rejected(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $user = User::factory()->create();
        $template = Template::query()->where('slug', 'powder-gold')->sole();
        $template->forceFill(['is_active' => false])->save();

        $this->actingAs($user)->post('/app/events', $this->validPayload([
            'template_id' => $template->getKey(),
            'event_date' => '2020-01-01T10:00',
        ]))->assertSessionHasErrors(['template_id', 'event_date']);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_event_view_and_dashboard_are_owner_only_even_for_admin(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $event = Event::factory()->create(['user_id' => $owner->getKey(), 'title' => 'مناسبة خاصة']);
        EventDesign::factory()->create(['event_id' => $event->getKey()]);
        $otherEvent = Event::factory()->create(['user_id' => $other->getKey(), 'title' => 'مناسبة أخرى']);
        EventDesign::factory()->create(['event_id' => $otherEvent->getKey()]);

        $this->actingAs($owner)->withoutVite()->get("/app/events/{$event->getKey()}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('App/Events/Show', false)
                ->where('event.id', (string) $event->getKey())
                ->where('event.title', 'مناسبة خاصة'));

        $this->actingAs($other)->get("/app/events/{$event->getKey()}")->assertForbidden();
        $this->actingAs($admin)->get("/app/events/{$event->getKey()}")->assertForbidden();

        $this->actingAs($owner)->withoutVite()->get('/app')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('events', 1)
                ->where('events.0.id', (string) $event->getKey())
                ->where('events.0.title', 'مناسبة خاصة'));
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'مناسبة تجريبية',
            'category' => EventCategory::Wedding->value,
            'template_id' => null,
            'event_date' => '2027-12-20T19:30',
            'timezone' => 'Africa/Tripoli',
        ], $overrides);
    }
}
