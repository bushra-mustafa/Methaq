<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Events\Models\Event;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SecretExposureTest extends TestCase
{
    use RefreshDatabase;

    /** Keys that must never appear in what a page hands to the browser (a path like /reset-password is fine, a field named password is not). */
    private const FORBIDDEN_IN_PROPS = ['"receipt_path"', '"receipt_sha256"', '"receiptPath"', '"two_factor', '"remember_token"', '"password"', 'APP_KEY', '"secret"'];

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Security regression tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_private_files_are_never_reachable_through_the_storage_url(): void
    {
        Storage::disk('local')->put('payments/receipts/1/probe.png', 'private');

        try {
            foreach (['/storage/payments/receipts/1/probe.png', '/storage/editor/assets/v1/fonts/arabic/Amiri-Regular.ttf'] as $path) {
                $this->assertContains($this->get($path)->getStatusCode(), [403, 404], $path.' must not be served');
            }

            $this->assertContains($this->put('/storage/payments/receipts/1/probe.png', ['x' => 'y'])->getStatusCode(), [403, 404, 405]);
        } finally {
            Storage::disk('local')->delete('payments/receipts/1/probe.png');
        }
    }

    public function test_pages_never_hand_receipts_hashes_or_credentials_to_the_browser(): void
    {
        $event = Event::factory()->create();
        $order = Order::factory()->manualTransfer()->create(['event_id' => $event->getKey(), 'user_id' => $event->user_id]);
        $owner = User::query()->findOrFail($event->user_id);
        $this->actingAs($owner);

        foreach (['/app', '/app/profile', "/app/events/{$event->getKey()}", "/app/events/{$event->getKey()}/payment"] as $path) {
            $props = json_encode($this->withoutVite()->get($path)->assertOk()->viewData('page')['props'], JSON_THROW_ON_ERROR);

            foreach (self::FORBIDDEN_IN_PROPS as $needle) {
                $this->assertStringNotContainsString($needle, $props, "{$path} leaks {$needle}");
            }

            $this->assertStringNotContainsString((string) $order->receipt_sha256, $props);
            $this->assertStringNotContainsString((string) $order->receipt_path, $props);
        }
    }

    public function test_built_frontend_bundles_contain_no_application_secrets(): void
    {
        $bundles = glob(public_path('build/assets/*.js')) ?: [];

        if ($bundles === []) {
            $this->markTestSkipped('No production build is present.');
        }

        $secrets = array_filter([(string) config('app.key'), (string) config('database.connections.mysql.password')], static fn (string $value): bool => strlen($value) >= 8);

        foreach ($bundles as $bundle) {
            $contents = (string) file_get_contents($bundle);
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, $contents, basename($bundle).' embeds a secret');
            }
        }
    }
}
