<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Actions\ClaimFreeOfferAction;
use App\Domains\Payments\Actions\DeleteFreeOfferAction;
use App\Domains\Payments\Actions\SaveFreeOfferAction;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Exceptions\PaymentReviewException;
use App\Domains\Payments\Models\FreeOffer;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\FreeOffers\Pages\ManageFreeOffers;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class FreeOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Free offer tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
    }

    public function test_a_customer_claims_the_offer_and_the_event_becomes_paid_with_a_zero_amount_order(): void
    {
        $offer = FreeOffer::factory()->create(['max_claims' => 4]);
        $event = $this->draftEvent();

        $this->actingAs($event->user)->post("/app/events/{$event->getKey()}/free-offer")->assertRedirect("/app/events/{$event->getKey()}/payment");

        $order = Order::query()->where('event_id', $event->getKey())->firstOrFail();
        $this->assertSame(PaymentGateway::FreeOffer, $order->gateway);
        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame('0', (string) $order->amount);
        $this->assertSame('free-offer-'.$offer->claims()->firstOrFail()->getKey(), $order->transaction_id);
        $this->assertTrue($event->fresh()->is_paid);
        $this->assertNotNull($event->fresh()->paid_at);
        $this->assertTrue(app(EventEntitlements::class)->completedOrder($event)?->is($order) ?? false);
        $this->assertDatabaseHas('audit_logs', ['action' => 'free_offer.claimed', 'subject_id' => $offer->getKey()]);

        $this->actingAs($event->user)->withoutVite()->get("/app/events/{$event->getKey()}/payment")
            ->assertInertia(fn (Assert $page) => $page->where('payment.state', 'paid')->where('payment.paidByOffer', true));
    }

    public function test_places_are_limited_and_each_account_may_use_the_offer_once(): void
    {
        FreeOffer::factory()->create(['max_claims' => 2]);
        $first = $this->draftEvent();
        $second = $this->draftEvent();
        $third = $this->draftEvent();
        $claim = app(ClaimFreeOfferAction::class);

        $claim->execute($first->user, $first);
        $claim->execute($second->user, $second);
        $this->assertClaimRefused(fn () => $claim->execute($third->user, $third));
        $this->assertSame(2, DB::table('free_offer_claims')->count());
        $this->assertFalse($third->fresh()->is_paid);

        FreeOffer::query()->update(['max_claims' => 10]);
        $another = Event::factory()->create(['user_id' => $first->user_id]);
        $this->assertClaimRefused(fn () => $claim->execute($first->user, $another));
    }

    public function test_an_offer_outside_its_window_or_switched_off_or_for_another_template_gives_nothing(): void
    {
        $claim = app(ClaimFreeOfferAction::class);
        $event = $this->draftEvent();

        foreach ([
            ['is_active' => false],
            ['starts_at' => now()->addHour(), 'ends_at' => now()->addDays(2)],
            ['starts_at' => now()->subDays(4), 'ends_at' => now()->subMinute()],
            ['template_id' => Template::factory()->create()->getKey()],
        ] as $attributes) {
            FreeOffer::query()->delete();
            FreeOffer::factory()->create($attributes);
            $this->assertClaimRefused(fn () => $claim->execute($event->user, $event));
        }
        $this->assertFalse($event->fresh()->is_paid);

        FreeOffer::query()->delete();
        FreeOffer::factory()->create(['template_id' => $event->template_id]);
        $claim->execute($event->user, $event);
        $this->assertTrue($event->fresh()->is_paid);
    }

    public function test_only_the_owner_of_an_unpaid_draft_can_claim(): void
    {
        FreeOffer::factory()->create();
        $event = $this->draftEvent();
        $claim = app(ClaimFreeOfferAction::class);

        $this->post("/app/events/{$event->getKey()}/free-offer")->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post("/app/events/{$event->getKey()}/free-offer")->assertForbidden();

        Order::factory()->create(['event_id' => $event->getKey(), 'user_id' => $event->user_id, 'status' => OrderStatus::Pending, 'gateway' => PaymentGateway::ManualTransfer, 'receipt_path' => 'x.png', 'receipt_sha256' => str_repeat('a', 64)]);
        $this->assertClaimRefused(fn () => $claim->execute($event->user, $event));

        $paid = $this->draftEvent();
        $paid->forceFill(['is_paid' => true, 'paid_at' => now()])->save();
        $this->assertClaimRefused(fn () => $claim->execute($paid->user, $paid));
        $this->assertSame(0, DB::table('free_offer_claims')->count());
    }

    public function test_the_home_page_and_the_payment_page_show_only_a_live_offer_with_places_left(): void
    {
        $this->withoutVite()->get('http://localhost/')->assertInertia(fn (Assert $page) => $page->where('freeOffer', null));

        $offer = FreeOffer::factory()->create(['title' => 'مجاناً لأول 4', 'max_claims' => 4, 'ends_at' => now()->addDays(3)]);
        $this->withoutVite()->get('http://localhost/')->assertInertia(fn (Assert $page) => $page
            ->where('freeOffer.title', 'مجاناً لأول 4')
            ->where('freeOffer.spotsTotal', 4)
            ->where('freeOffer.spotsLeft', 4)
            ->where('freeOffer.ctaHref', '/app/events/create'));

        $event = $this->draftEvent();
        $this->actingAs($event->user)->withoutVite()->get("http://localhost/app/events/{$event->getKey()}/payment")
            ->assertInertia(fn (Assert $page) => $page->where('payment.freeOffer.spotsLeft', 4));

        $offer->update(['max_claims' => 1]);
        app(ClaimFreeOfferAction::class)->execute($event->user, $event);
        $this->withoutVite()->get('http://localhost/')->assertInertia(fn (Assert $page) => $page->where('freeOffer', null));
    }

    public function test_the_administrator_manages_offers_and_the_rules_hold(): void
    {
        $admin = $this->administrator();
        $save = app(SaveFreeOfferAction::class);
        $base = ['title' => 'عرض', 'description' => null, 'template_id' => null, 'max_claims' => 4, 'starts_at' => now(), 'ends_at' => now()->addDays(3), 'is_active' => true];

        foreach ([['max_claims' => 0], ['ends_at' => now()->subDay()], ['ends_at' => now()->addDays(120)], ['title' => ' '], ['template_id' => 999999]] as $bad) {
            try {
                $save->execute($admin, [...$base, ...$bad]);
                $this->fail('An invalid offer was saved.');
            } catch (ValidationException) {
                // expected
            }
        }
        $this->assertSame(0, FreeOffer::query()->count());

        $offer = $save->execute($admin, $base);
        $event = $this->draftEvent();
        app(ClaimFreeOfferAction::class)->execute($event->user, $event);

        try {
            $save->execute($admin, [...$base, 'max_claims' => 0 + 1], (int) $offer->getKey());
            $this->assertSame(1, $offer->fresh()->max_claims);
            $this->assertTrue(true);
        } catch (ValidationException) {
            $this->fail('A place count equal to the claims taken must be allowed.');
        }

        $this->expectException(PaymentReviewException::class);
        app(DeleteFreeOfferAction::class)->execute($admin, (int) $offer->getKey());
    }

    public function test_customers_cannot_manage_offers_and_the_admin_page_works(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer)->get('/admin/free-offers')->assertForbidden();
        try {
            app(SaveFreeOfferAction::class)->execute($customer, ['title' => 'x', 'max_claims' => 1, 'starts_at' => now(), 'ends_at' => now()->addDay(), 'is_active' => true]);
            $this->fail('A customer saved an offer.');
        } catch (AuthorizationException) {
            $this->assertSame(0, FreeOffer::query()->count());
        }

        $this->actingAs($this->administrator());
        Livewire::test(ManageFreeOffers::class)
            ->callAction('create', ['title' => 'مجاناً لأول 4 مصمّمات', 'description' => '', 'template_id' => null, 'max_claims' => 4, 'starts_at' => now()->toDateTimeString(), 'ends_at' => now()->addDays(3)->toDateTimeString(), 'is_active' => true])
            ->assertHasNoFormErrors();
        $offer = FreeOffer::query()->firstOrFail();
        $this->assertSame(4, $offer->max_claims);

        Livewire::test(ManageFreeOffers::class)
            ->assertCanSeeTableRecords([$offer])
            ->callAction(TestAction::make('remove')->table($offer));
        $this->assertDatabaseCount('free_offers', 0);
    }

    private function assertClaimRefused(callable $attempt): void
    {
        try {
            $attempt();
            $this->fail('The claim was accepted.');
        } catch (ValidationException) {
            // expected
        }
    }

    private function draftEvent(): Event
    {
        $template = Template::factory()->create(['price_minor' => 25_000]);

        return Event::factory()->create(['template_id' => $template->getKey()])->load(['user', 'template']);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
