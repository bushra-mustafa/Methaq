<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Enums\UserStatus;
use App\Domains\Users\Models\User;
use App\Filament\Resources\Events\Pages\ManageEvents;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminDraftReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Draft review tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_an_administrator_with_two_factor_reads_a_draft_read_only_and_every_view_is_audited(): void
    {
        $event = $this->eventWithDraft();

        $this->actingAs($this->administrator())->get("/app/review/events/{$event->getKey()}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('App/DraftReview')
                ->where('event.title', 'مسودة خاصة')
                ->where('owner.email', $event->user->email)
                ->has('document.canvas'));
        $this->actingAs($this->administrator())->get("/app/review/events/{$event->getKey()}")->assertOk();

        $this->assertSame(2, DB::table('audit_logs')->where('action', 'event.draft_viewed')->where('subject_id', $event->getKey())->count());
    }

    public function test_the_owner_other_customers_guests_and_unsafe_admins_cannot_use_the_review_route(): void
    {
        $event = $this->eventWithDraft();
        $url = "/app/review/events/{$event->getKey()}";

        $this->get($url)->assertRedirect('/login');
        $this->actingAs($event->user)->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();

        $noTwoFactor = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($noTwoFactor)->get($url)->assertForbidden();

        $suspended = $this->administrator();
        $suspended->forceFill(['status' => UserStatus::Suspended])->save();
        $this->actingAs($suspended)->get($url)->assertRedirect();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'event.draft_viewed']);
    }

    public function test_the_review_route_cannot_save_or_change_the_draft(): void
    {
        $event = $this->eventWithDraft();
        $admin = $this->administrator();
        $revision = $event->design->revision;

        $this->actingAs($admin)->patch("/app/events/{$event->getKey()}/design", ['revision' => $revision])->assertForbidden();
        $this->assertSame($revision, $event->design->fresh()->revision);
    }

    public function test_the_events_table_offers_the_draft_link_to_administrators(): void
    {
        Filament::setCurrentPanel('admin');
        $event = $this->eventWithDraft();
        $this->actingAs($this->administrator());

        Livewire::test(ManageEvents::class)
            ->assertActionVisible(TestAction::make('draft')->table($event))
            ->assertActionHasUrl(TestAction::make('draft')->table($event), route('app.review.events.show', $event));
    }

    private function eventWithDraft(): Event
    {
        $event = Event::factory()->create(['title' => 'مسودة خاصة']);
        EventDesign::factory()->create(['event_id' => $event->getKey()]);

        return $event->fresh(['user', 'design']);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
