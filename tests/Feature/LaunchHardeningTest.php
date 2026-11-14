<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Actions\ExpirePublishedInvitationsAction;
use App\Domains\Events\Actions\ReinstateEventAction;
use App\Domains\Events\Actions\SuspendEventAction;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\RSVP\Models\Rsvp;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class LaunchHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Hardening tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_env_is_read_only_inside_config_files_so_config_cache_keeps_every_setting(): void
    {
        $offenders = [];
        foreach (['app', 'bootstrap', 'routes', 'database'] as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory), RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->getExtension() === 'php' && $this->callsEnv((string) file_get_contents($file->getPathname()))) {
                    $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $offenders, 'env() outside config/ returns null once the configuration is cached.');
    }

    public function test_an_event_stops_taking_new_replies_at_its_limit_but_a_resend_still_updates(): void
    {
        config(['methaq.limits.max_rsvps_per_event' => 2]);
        $event = $this->publishedEvent();
        $host = 'http://'.$event->subdomain.'.'.config('methaq.invitation_domain');
        $reply = fn (string $token, string $name): array => ['submission_token' => $token, 'guest_name' => $name, 'attending_status' => 'attending', 'companions_count' => 0];

        $this->post($host.'/rsvp', $reply('11111111-1111-4111-8111-111111111111', 'أولى'))->assertRedirect();
        $this->post($host.'/rsvp', $reply('22222222-2222-4222-8222-222222222222', 'ثانية'))->assertRedirect();
        $this->post($host.'/rsvp', $reply('33333333-3333-4333-8333-333333333333', 'ثالثة'))->assertSessionHasErrors('rsvp');
        $this->post($host.'/rsvp', $reply('11111111-1111-4111-8111-111111111111', 'أولى معدّلة'))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, Rsvp::query()->where('event_id', $event->getKey())->count());
        $this->assertDatabaseHas('rsvps', ['guest_name' => 'أولى معدّلة']);
    }

    public function test_the_reply_limit_is_per_event_so_one_venue_network_is_not_shared_across_invitations(): void
    {
        $first = $this->publishedEvent();
        $second = Event::factory()->create(['subdomain' => 'second-wedding-2027', 'status' => EventStatus::Published, 'is_paid' => true, 'paid_at' => now(), 'published_at' => now()]);
        $this->designFor($second);
        $hostOne = 'http://'.$first->subdomain.'.'.config('methaq.invitation_domain');
        $hostTwo = 'http://'.$second->subdomain.'.'.config('methaq.invitation_domain');
        $payload = fn (): array => ['submission_token' => fake()->uuid(), 'guest_name' => 'ضيفة', 'attending_status' => 'attending', 'companions_count' => 0];

        for ($i = 0; $i < 20; $i++) {
            $this->post($hostOne.'/rsvp', $payload())->assertRedirect();
        }
        $this->post($hostOne.'/rsvp', $payload())->assertStatus(429);
        $this->post($hostTwo.'/rsvp', $payload())->assertRedirect();
    }

    public function test_invitation_addresses_serve_only_the_invitation_and_www_goes_to_the_main_site(): void
    {
        $event = $this->publishedEvent();
        $domain = config('methaq.invitation_domain');
        $guest = 'http://'.$event->subdomain.'.'.$domain;

        $this->withoutVite()->get($guest.'/')->assertOk();
        foreach (['/login', '/register', '/app', '/admin', '/forgot-password', '/templates'] as $path) {
            $this->get($guest.$path)->assertNotFound();
        }

        $this->get('http://www.'.$domain.'/templates')->assertRedirect(rtrim((string) config('app.url'), '/').'/templates')->assertStatus(301);
        $this->withoutVite()->get('http://localhost/login')->assertOk();
    }

    public function test_one_address_cannot_try_passwords_on_unlimited_accounts(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/login', ['email' => "victim{$i}@example.test", 'password' => 'wrong-password'])->assertSessionHasErrors();
        }

        $this->post('/login', ['email' => 'victim99@example.test', 'password' => 'wrong-password'])->assertStatus(429);
    }

    public function test_suspending_or_expiring_an_invitation_takes_its_card_image_off_the_public_disk_and_reinstating_returns_it(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $event = $this->publishedEvent();
        $relative = 'renders/invitations/'.$event->getKey().'/card.png';
        $event->design->forceFill(['final_render_path' => 'storage/'.$relative])->save();
        Storage::disk('public')->put($relative, 'png-bytes');
        $admin = $this->administrator();

        app(SuspendEventAction::class)->execute($admin, (int) $event->getKey(), 'محتوى مخالف');
        Storage::disk('public')->assertMissing($relative);
        Storage::disk('local')->assertExists('renders-held/'.$relative);

        app(ReinstateEventAction::class)->execute($admin, (int) $event->getKey());
        Storage::disk('public')->assertExists($relative);
        Storage::disk('local')->assertMissing('renders-held/'.$relative);

        $event->forceFill(['event_date' => now()->subDays(10), 'expires_at' => now()->subMinute()])->save();
        $this->assertSame(1, app(ExpirePublishedInvitationsAction::class)->execute());
        $this->assertSame(EventStatus::Expired, $event->fresh()->status);
        Storage::disk('public')->assertMissing($relative);
        Storage::disk('local')->assertExists('renders-held/'.$relative);
    }

    /** True when the code calls env(); comments and strings that merely mention it do not count. */
    private function callsEnv(string $source): bool
    {
        $tokens = token_get_all($source);
        foreach ($tokens as $index => $token) {
            if (is_array($token) && $token[0] === T_STRING && $token[1] === 'env' && ($tokens[$index + 1] ?? null) === '(' && ! (is_array($tokens[$index - 1] ?? null) && in_array($tokens[$index - 1][0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true))) {
                return true;
            }
        }

        return false;
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
        $this->designFor($event);

        return $event->fresh('design');
    }

    private function designFor(Event $event): void
    {
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
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
