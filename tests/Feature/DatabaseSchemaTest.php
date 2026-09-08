<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\AssetCollectionItem;
use App\Domains\Editor\Models\DesignRender;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Models\Order;
use App\Domains\Payments\Models\PaymentWebhook;
use App\Domains\RSVP\Models\Rsvp;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\AuditLog;
use App\Domains\Users\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Schema verification requires an isolated MySQL 8 test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_all_business_tables_exist_and_no_demo_account_is_seeded(): void
    {
        foreach (['users', 'templates', 'template_assets', 'template_asset_links', 'asset_collections', 'asset_collection_items', 'events', 'event_designs', 'orders', 'payment_webhooks', 'design_renders', 'rsvps', 'audit_logs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        $this->seed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_domain_relationships_and_casts_round_trip(): void
    {
        $template = Template::factory()->create();
        $asset = TemplateAsset::factory()->create();
        $template->assets()->attach($asset, ['sort_order' => 2]);
        $item = AssetCollectionItem::factory()->create(['template_asset_id' => $asset->id]);
        $event = Event::factory()->create(['template_id' => $template->id]);
        $design = EventDesign::factory()->create(['event_id' => $event->id]);
        $render = DesignRender::factory()->create(['event_design_id' => $design->id]);
        $order = Order::factory()->create(['event_id' => $event->id]);
        $webhook = PaymentWebhook::factory()->create(['order_id' => $order->id]);
        $rsvp = Rsvp::factory()->create(['event_id' => $event->id]);
        $log = AuditLog::factory()->create(['actor_type' => 'user', 'actor_id' => $event->user_id]);

        $this->assertTrue($template->assets->first()->is($asset));
        $this->assertTrue($asset->templates->first()->is($template));
        $this->assertTrue($item->collection->items->first()->is($item));
        $this->assertTrue($event->design->is($design));
        $this->assertTrue($design->renders->first()->is($render));
        $this->assertTrue($order->user->is($event->user));
        $this->assertTrue($order->webhooks->first()->is($webhook));
        $this->assertTrue($event->rsvps->first()->is($rsvp));
        $this->assertTrue($log->actor->is($event->user));
        $this->assertSame(EventStatus::Draft, $event->fresh()->status);
        $this->assertSame(UserRole::Customer, $event->user->role);
        $this->assertIsArray($design->fresh()->scene_json);
        $this->assertSame('1000', $order->fresh()->amount);
    }

    public function test_order_cannot_belong_to_a_different_owner(): void
    {
        $event = Event::factory()->create();
        $other = User::factory()->create();
        $this->rejects(fn () => Order::factory()->create(['event_id' => $event->id, 'user_id' => $other->id]), 1452);
    }

    public function test_blank_canvas_event_does_not_require_a_template(): void
    {
        $this->assertNull(Event::factory()->create()->template_id);
    }

    #[DataProvider('invalidEventValues')]
    public function test_event_invariants_are_enforced_by_mysql(array $attributes): void
    {
        $event = Event::factory()->create();
        $this->rejects(fn () => DB::table('events')->where('id', $event->id)->update($attributes), 3819);
    }

    public static function invalidEventValues(): array
    {
        return [
            'invalid status' => [['status' => 'unknown']],
            'uppercase status' => [['status' => 'DRAFT']],
            'unpaid publish' => [['status' => 'published', 'published_at' => '2026-01-01']],
            'paid without date' => [['is_paid' => true]],
            'expiry before event' => [['expires_at' => '2000-01-01']],
            'uppercase domain' => [['subdomain' => 'Wrong-Case']],
            'invalid domain' => [['subdomain' => '-bad-']],
        ];
    }

    public function test_slug_is_unique_and_foreign_keys_restrict_deletion(): void
    {
        $event = Event::factory()->create();
        $this->rejects(fn () => Event::factory()->create(['subdomain' => $event->subdomain]), 1062);
        $this->rejects(fn () => $event->user->delete(), 1451);
    }

    public function test_design_is_unique_and_published_fields_are_atomic(): void
    {
        $design = EventDesign::factory()->create();
        $this->rejects(fn () => EventDesign::factory()->create(['event_id' => $design->event_id]), 1062);
        $this->rejects(fn () => DB::table('event_designs')->where('id', $design->id)->update(['final_render_path' => 'partial.png']), 3819);
        $this->rejects(fn () => DB::table('event_designs')->where('id', $design->id)->update(['revision' => 0]), 3819);
    }

    public function test_payment_keys_and_currency_are_enforced(): void
    {
        $order = Order::factory()->create(['transaction_id' => 'txn_same', 'checkout_id' => 'checkout_same']);
        foreach (['transaction_id', 'checkout_id', 'idempotency_key'] as $key) {
            $this->rejects(fn () => Order::factory()->create([$key => $order->{$key}]), 1062);
        }
        $this->rejects(fn () => DB::table('orders')->where('id', $order->id)->update(['amount' => 0]), 3819);
        $this->rejects(fn () => DB::table('orders')->where('id', $order->id)->update(['currency' => 'usd']), 3819);
        Order::factory()->count(2)->create();
        $this->assertDatabaseCount('orders', 3);
    }

    public function test_payment_provider_identifiers_are_case_sensitive(): void
    {
        Order::factory()->create(['transaction_id' => 'Txn123']);
        Order::factory()->create(['transaction_id' => 'txn123']);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_webhook_and_render_delivery_keys_are_unique(): void
    {
        $webhook = PaymentWebhook::factory()->create();
        $this->rejects(fn () => PaymentWebhook::factory()->create(['gateway_event_id' => $webhook->gateway_event_id]), 1062);
        $render = DesignRender::factory()->create();
        $this->rejects(fn () => DesignRender::factory()->create(['event_design_id' => $render->event_design_id]), 1062);
        $this->rejects(fn () => DB::table('design_renders')->where('id', $render->id)->update(['status' => 'completed']), 3819);
    }

    public function test_rsvp_tokens_are_scoped_and_declined_guests_have_no_companions(): void
    {
        $rsvp = Rsvp::factory()->create();
        $this->rejects(fn () => Rsvp::factory()->create(['event_id' => $rsvp->event_id, 'submission_token' => $rsvp->submission_token]), 1062);
        Rsvp::factory()->create(['submission_token' => $rsvp->submission_token]);
        $this->rejects(fn () => DB::table('rsvps')->where('id', $rsvp->id)->update(['attending_status' => 'declined', 'companions_count' => 1]), 3819);
        $this->assertDatabaseCount('rsvps', 2);
    }

    public function test_collection_positions_are_unique_and_items_cascade(): void
    {
        $collection = AssetCollection::factory()->create();
        $item = AssetCollectionItem::factory()->create(['collection_id' => $collection->id]);
        $this->rejects(fn () => AssetCollectionItem::factory()->create(['collection_id' => $collection->id]), 1062);
        $this->rejects(fn () => $item->asset->delete(), 1451);
        $collection->delete();
        $this->assertDatabaseMissing('asset_collection_items', ['id' => $item->id]);
    }

    public function test_registration_cannot_mass_assign_role_and_two_factor_data_is_encrypted(): void
    {
        $user = new User;
        $user->fill(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'a-test-password', 'role' => 'admin', 'status' => 'suspended']);
        $user->save();
        $this->assertSame(UserRole::Customer, $user->fresh()->role);
        $user->two_factor_secret = 'test-secret';
        $user->two_factor_recovery_codes = ['test-code'];
        $user->save();
        $this->assertNotSame('test-secret', DB::table('users')->where('id', $user->id)->value('two_factor_secret'));
        $this->assertSame(['test-code'], $user->fresh()->two_factor_recovery_codes);
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
    }

    public function test_timestamps_preserve_microseconds_and_audit_has_no_updated_at(): void
    {
        $event = Event::factory()->create(['event_date' => '2027-01-01 12:34:56.123456']);
        $this->assertSame('123456', $event->fresh()->event_date->format('u'));
        $log = AuditLog::factory()->create();
        $this->assertNotNull($log->created_at);
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));
        $this->rejects(fn () => AuditLog::factory()->create(['actor_type' => 'user']), 3819);
    }

    private function rejects(Closure $operation, int $code): void
    {
        try {
            $operation();
            $this->fail('Expected a database constraint violation.');
        } catch (QueryException $exception) {
            $this->assertSame($code, $exception->errorInfo[1]);
        }
    }
}
