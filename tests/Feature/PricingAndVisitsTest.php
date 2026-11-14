<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\UpdateBlankCardPriceAction;
use App\Domains\Payments\Services\ManualPaymentEligibility;
use App\Domains\Payments\Services\PricingSettings;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Events\Pages\ManageEvents;
use App\Filament\Resources\Templates\Pages\ManageTemplates;
use App\Filament\Widgets\VisitorsOverview;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class PricingAndVisitsTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/605.1';

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Pricing and visit tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
        // Counters may hold rows left behind by tests that commit early (DDL); start each test from zero.
        DB::table('event_visit_days')->delete();
        DB::table('site_visit_days')->delete();
    }

    public function test_a_blank_card_costs_fifty_dinars_by_default_and_a_template_keeps_its_own_price(): void
    {
        $blank = Event::factory()->create(['template_id' => null]);
        $templated = Event::factory()->create(['template_id' => Template::factory()->create(['price_minor' => 25_500])->getKey()]);
        $eligibility = app(ManualPaymentEligibility::class);

        $this->assertSame(50_000, $eligibility->priceMinor($blank));
        $this->assertSame(25_500, $eligibility->priceMinor($templated->load('template')));

        $this->actingAs($blank->user)->withoutVite()->get("/app/events/{$blank->getKey()}/payment")
            ->assertInertia(fn (Assert $page) => $page->where('payment.priceLabel', '50 د.ل'));
        $this->actingAs($blank->user)->withoutVite()->get('/app/events/create')
            ->assertInertia(fn (Assert $page) => $page->where('blankCard.available', true)->where('blankCard.priceLabel', '50 د.ل'));
    }

    public function test_an_administrator_changes_the_blank_card_price_from_the_templates_page_and_zero_closes_it(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);

        Livewire::test(ManageTemplates::class)->callAction('blankPrice', ['price' => 75.5])->assertHasNoFormErrors();
        $this->assertSame(75_500, app(PricingSettings::class)->blankCardPriceMinor());
        $this->assertDatabaseHas('audit_logs', ['action' => 'pricing.blank_card_updated']);

        $blank = Event::factory()->create(['template_id' => null]);
        $this->assertSame(75_500, app(ManualPaymentEligibility::class)->priceMinor($blank));

        app(UpdateBlankCardPriceAction::class)->execute($admin, 0);
        $this->actingAs($blank->user)->withoutVite()->get('/app/events/create')
            ->assertInertia(fn (Assert $page) => $page->where('blankCard.available', false));
        $this->actingAs($blank->user)->post('/app/events', ['title' => 'من الصفر', 'category' => 'wedding', 'template_id' => null, 'event_date' => now()->addMonth()->format('Y-m-d\TH:i'), 'timezone' => 'Africa/Tripoli'])
            ->assertSessionHasErrors('template_id');
    }

    public function test_only_an_administrator_can_set_the_price_and_it_must_be_in_range(): void
    {
        try {
            app(UpdateBlankCardPriceAction::class)->execute(User::factory()->create(), 1_000);
            $this->fail('A customer changed the price.');
        } catch (AuthorizationException) {
            $this->assertSame(50_000, app(PricingSettings::class)->blankCardPriceMinor());
        }

        $this->expectException(ValidationException::class);
        app(UpdateBlankCardPriceAction::class)->execute($this->administrator(), -5);
    }

    public function test_an_invitation_view_is_counted_once_per_visitor_per_day_and_robots_and_previews_are_not(): void
    {
        $event = $this->publishedEvent();
        $url = 'http://'.$event->subdomain.'.'.config('methaq.invitation_domain').'/';
        $browser = ['User-Agent' => self::BROWSER];

        $this->withoutVite()->withHeaders($browser)->get($url)->assertOk();
        $this->withoutVite()->withHeaders($browser)->get($url)->assertOk();
        $this->withoutVite()->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120'])->get($url)->assertOk();
        $this->withoutVite()->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->get($url)->assertOk();
        $this->withoutVite()->withHeaders([...$browser, 'Purpose' => 'prefetch'])->get($url)->assertOk();
        $this->withoutVite()->get($url)->assertOk();

        $row = DB::table('event_visit_days')->where('event_id', $event->getKey())->first();
        $this->assertSame([3, 2], [(int) $row->views, (int) $row->visitors]);
        $this->assertSame(0, DB::table('site_visit_days')->count());
    }

    public function test_a_missing_expired_or_unpaid_invitation_is_not_counted(): void
    {
        $event = $this->publishedEvent();
        $event->forceFill(['event_date' => now()->subDays(10), 'expires_at' => now()->subMinute()])->save();

        $this->withoutVite()->withHeaders(['User-Agent' => self::BROWSER])->get('http://'.$event->subdomain.'.'.config('methaq.invitation_domain').'/')->assertStatus(410);
        $this->withoutVite()->withHeaders(['User-Agent' => self::BROWSER])->get('http://nobody-2027.'.config('methaq.invitation_domain').'/')->assertNotFound();

        $this->assertSame(0, DB::table('event_visit_days')->count());
    }

    public function test_public_pages_are_counted_for_the_site_but_the_account_area_and_administrators_are_not(): void
    {
        $browser = ['User-Agent' => self::BROWSER];
        $this->withoutVite()->withHeaders($browser)->get('/')->assertOk();
        $this->withoutVite()->withHeaders($browser)->get('/templates')->assertOk();
        $this->withoutVite()->withHeaders($browser)->get('/login')->assertOk();
        $this->actingAs(User::factory()->create())->withoutVite()->withHeaders($browser)->get('/app')->assertOk();
        $this->actingAs($this->administrator())->withoutVite()->withHeaders($browser)->get('/about')->assertOk();

        $row = DB::table('site_visit_days')->first();
        $this->assertSame([2, 1], [(int) $row->views, (int) $row->visitors]);
    }

    public function test_the_owner_and_the_administrator_see_the_numbers(): void
    {
        $event = $this->publishedEvent();
        $url = 'http://'.$event->subdomain.'.'.config('methaq.invitation_domain').'/';
        $this->withoutVite()->withHeaders(['User-Agent' => self::BROWSER])->get($url);
        $this->withoutVite()->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120'])->get($url);
        $this->withoutVite()->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/120'])->get($url);

        $this->actingAs($event->user)->withoutVite()->get("http://localhost/app/events/{$event->getKey()}")
            ->assertInertia(fn (Assert $page) => $page->where('visits.views', 3)->where('visits.visitors', 2)->where('visits.last7Views', 3));
        $this->actingAs(User::factory()->create())->get("http://localhost/app/events/{$event->getKey()}")->assertForbidden();

        $this->actingAs($this->administrator());
        Livewire::test(ManageEvents::class)
            ->assertTableColumnStateSet('views_total', 3, $event)
            ->assertTableColumnStateSet('visitors_total', 2, $event);

        $this->withoutVite()->withHeaders(['User-Agent' => self::BROWSER])->get('http://localhost/');
        Livewire::test(VisitorsOverview::class)->assertSee('زوار الموقع')->assertSee('فتحة صفحة');
    }

    private function publishedEvent(): Event
    {
        $event = Event::factory()->create(['subdomain' => 'counted-wedding-2027', 'status' => EventStatus::Published, 'is_paid' => true, 'paid_at' => now(), 'published_at' => now()]);
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

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
