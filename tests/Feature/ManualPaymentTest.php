<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Models\Template;
use App\Domains\Events\Models\Event;
use App\Domains\Payments\Enums\OrderStatus;
use App\Domains\Payments\Enums\PaymentGateway;
use App\Domains\Payments\Models\Order;
use App\Domains\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class ManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Manual payment integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['methaq.payment.bank_transfer' => [
            'bank_name' => 'مصرف الجمهورية',
            'account_name' => 'ميثاق',
            'account_number' => '0012345678',
            'iban' => null,
        ]]);
    }

    public function test_owner_uploads_a_receipt_as_a_pending_order_with_a_price_and_feature_snapshot(): void
    {
        $event = $this->draftEvent(priceMinor: 25_000, locked: [TemplateFeature::Music]);

        $this->actingAs($event->user)
            ->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.jpg', 600, 900)])
            ->assertRedirect("/app/events/{$event->getKey()}/payment")
            ->assertSessionHas('status', 'receipt-submitted');

        $order = Order::query()->sole();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentGateway::ManualTransfer, $order->gateway);
        $this->assertSame('25000', $order->amount);
        $this->assertSame('LYD', $order->currency);
        $this->assertFalse($order->entitlements->contains(TemplateFeature::Music));
        $this->assertTrue($order->entitlements->contains(TemplateFeature::Rsvp));
        $this->assertStringStartsWith("payments/receipts/{$event->getKey()}/", $order->receipt_path);
        $this->assertStringEndsWith('.jpg', $order->receipt_path);
        $this->assertSame(64, strlen($order->receipt_sha256));
        Storage::disk('local')->assertExists($order->receipt_path);
        $this->assertFalse($event->refresh()->is_paid);

        $event->template->forceFill(['price_minor' => 40_000, 'locked_features' => []])->save();

        $this->assertSame('25000', $order->refresh()->amount);
        $this->assertFalse($order->entitlements->contains(TemplateFeature::Music));
        $this->withoutVite()->get("/app/events/{$event->getKey()}/payment")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('App/Events/Payment')
                ->where('payment.state', 'pending_review')
                ->where('payment.canSubmit', false)
                ->where('payment.latestOrder.amountLabel', '25 د.ل')
                ->missing('payment.latestOrder.receipt_path'));
    }

    public function test_payment_page_shows_the_price_account_and_locked_features_to_the_owner_only(): void
    {
        $event = $this->draftEvent(priceMinor: 25_500, locked: [TemplateFeature::HdDownload]);

        $this->actingAs($event->user)->withoutVite()->get("/app/events/{$event->getKey()}/payment")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('payment.state', 'awaiting_receipt')
                ->where('payment.priceLabel', '25.5 د.ل')
                ->where('payment.bankAccount.accountNumber', '0012345678')
                ->where('payment.lockedFeatures', ['التحميل بدقة عالية'])
                ->where('payment.canSubmit', true));

        $this->actingAs(User::factory()->create())->withoutVite()->get("/app/events/{$event->getKey()}/payment")->assertForbidden();
    }

    public function test_only_the_owner_can_upload_and_the_client_cannot_set_the_amount(): void
    {
        $event = $this->draftEvent(priceMinor: 25_000);

        $this->actingAs(User::factory()->create())
            ->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.png')])
            ->assertForbidden();

        $this->actingAs($event->user)
            ->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.png'), 'amount' => 1])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_receipt_must_be_an_image_or_pdf_within_five_megabytes(): void
    {
        $event = $this->draftEvent(priceMinor: 25_000);
        $this->actingAs($event->user);

        $disguised = tempnam(sys_get_temp_dir(), 'receipt');
        file_put_contents($disguised, '<?php echo 1;');
        $this->post("/app/events/{$event->getKey()}/payment", ['receipt' => new UploadedFile($disguised, 'receipt.jpg', 'image/jpeg', null, true)])
            ->assertSessionHasErrors('receipt');
        $this->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.jpg')->size(5121)])
            ->assertSessionHasErrors('receipt');
        $this->post("/app/events/{$event->getKey()}/payment", [])
            ->assertSessionHasErrors('receipt');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_one_receipt_awaits_review_per_event_and_a_rejection_allows_a_new_one(): void
    {
        $event = $this->draftEvent(priceMinor: 25_000);
        $this->actingAs($event->user);

        $this->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('first.jpg')])->assertSessionHasNoErrors();
        $this->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('second.jpg')])->assertSessionHasErrors('receipt');

        $this->assertDatabaseCount('orders', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());

        Order::query()->sole()->forceFill(['status' => OrderStatus::Failed, 'failure_code' => 'rejected'])->save();

        $this->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('third.jpg')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_upload_is_closed_without_a_price_an_account_or_a_future_draft(): void
    {
        $free = $this->draftEvent(priceMinor: 0);
        $this->actingAs($free->user)
            ->post("/app/events/{$free->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.jpg')])
            ->assertSessionHasErrors(['receipt' => 'لم يُحدَّد سعر لهذه المناسبة بعد. تواصلي معنا لإكمال الدفع.']);

        $past = $this->draftEvent(priceMinor: 25_000);
        $past->forceFill(['event_date' => now()->subDay(), 'expires_at' => now()->addDay()])->save();
        $this->actingAs($past->user)
            ->post("/app/events/{$past->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.jpg')])
            ->assertSessionHasErrors('receipt');

        config(['methaq.payment.bank_transfer.account_number' => '']);
        $unconfigured = $this->draftEvent(priceMinor: 25_000);
        $this->actingAs($unconfigured->user)
            ->post("/app/events/{$unconfigured->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.jpg')])
            ->assertSessionHasErrors('receipt');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_receipt_stays_private_and_the_database_requires_it_for_manual_transfers(): void
    {
        $event = $this->draftEvent(priceMinor: 25_000);
        $this->actingAs($event->user)->post("/app/events/{$event->getKey()}/payment", ['receipt' => UploadedFile::fake()->image('receipt.jpg')]);
        $path = Order::query()->sole()->receipt_path;

        // The private disk is no longer served at all (config/filesystems.php: serve => false).
        $this->get('/storage/'.$path)->assertNotFound();
        $this->assertFileDoesNotExist(public_path('storage/'.$path));

        $this->expectException(QueryException::class);
        Order::factory()->create(['event_id' => $event->getKey(), 'gateway' => PaymentGateway::ManualTransfer->value, 'currency' => 'LYD']);
    }

    /** @param list<TemplateFeature> $locked */
    private function draftEvent(int $priceMinor, array $locked = []): Event
    {
        $template = Template::factory()->create(['price_minor' => $priceMinor, 'locked_features' => $locked]);

        return Event::factory()->create(['template_id' => $template->getKey()])->load(['user', 'template']);
    }
}
