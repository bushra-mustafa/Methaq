<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Users\Actions\SaveCloudMsgSettingsAction;
use App\Domains\Users\Models\User;
use App\Domains\Users\Models\WhatsAppMessage;
use App\Domains\Users\Services\CloudMsg;
use App\Domains\Users\Services\CloudMsgSettings;
use App\Domains\Users\Services\WhatsAppOutbox;
use App\Filament\Pages\WhatsAppSettings;
use App\Jobs\SendWhatsAppMessage;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class WhatsAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires isolated MySQL.');
        }
        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a non-test database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel('admin');
        config(['cloudmsg.enabled' => false, 'cloudmsg.api_key' => null, 'cloudmsg.instance_id' => null, 'cloudmsg.webhook_secret' => null, 'cloudmsg.admin_phone' => null, 'cloudmsg.webhook_url' => null]);
        Http::preventStrayRequests();
    }

    public function test_admin_saves_from_panel_and_secrets_are_encrypted_and_not_returned(): void
    {
        $this->actingAs($this->admin());
        $component = Livewire::test(WhatsAppSettings::class)->fillForm($this->values())->call('save')->assertHasNoFormErrors();
        $component->assertSet('data.api_key', '')->assertSet('data.webhook_secret', '');
        $component->assertDontSee('new-api-secret')->assertDontSee('new-webhook-secret');
        $row = DB::table('settings')->where('key', CloudMsgSettings::KEY)->value('value');
        $this->assertStringNotContainsString('new-api-secret', $row);
        $this->assertStringNotContainsString('new-webhook-secret', $row);
        $audit = DB::table('audit_logs')->where('action', 'whatsapp.settings_updated')->sole();
        $this->assertStringNotContainsString('new-api-secret', $audit->metadata);
        $this->assertTrue(CloudMsgSettings::get('enabled'));
        Livewire::test(WhatsAppSettings::class)->assertSet('data.api_key', '')->assertSet('data.admin_phone', '+218910000099');
        $this->get('/admin/whatsapp-settings')->assertOk()->assertDontSee('new-api-secret');
    }

    public function test_saving_blank_secret_fields_preserves_them_and_disable_overrides_environment(): void
    {
        $admin = $this->admin();
        $save = app(SaveCloudMsgSettingsAction::class);
        $save->execute($admin, $this->values());
        $save->execute($admin, [...$this->values(), 'enabled' => false, 'api_key' => '', 'webhook_secret' => null]);
        config(['cloudmsg.enabled' => true]);
        $this->app->forgetScopedInstances();
        $this->assertFalse(CloudMsgSettings::get('enabled'));
        $this->assertSame('new-api-secret', CloudMsgSettings::get('api_key'));
        $this->assertSame('new-webhook-secret', CloudMsgSettings::get('webhook_secret'));
    }

    public function test_background_sender_uses_admin_settings_without_editing_config(): void
    {
        app(SaveCloudMsgSettingsAction::class)->execute($this->admin(), $this->values());
        $this->app->forgetScopedInstances();
        app(WhatsAppOutbox::class)->record('admin-config-test', '+218910000099', 'Hello');
        Http::fake(['cloudmsg.impaact.io/*' => Http::response(['data' => ['id' => 'msg_admin_settings']], 202)]);
        (new SendWhatsAppMessage(WhatsAppMessage::query()->sole()->id))->handle(app(CloudMsg::class));
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer new-api-secret') && $request['instance_id'] === 'inst_admin123');
        $this->assertFalse(config('cloudmsg.enabled'));
    }

    public function test_incomplete_configuration_cannot_be_enabled_and_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin());
        Livewire::test(WhatsAppSettings::class)->fillForm([...$this->values(), 'api_key' => ''])->call('save')->assertHasErrors(['data.api_key']);
        $this->assertDatabaseMissing('settings', ['key' => CloudMsgSettings::KEY]);
        Livewire::test(WhatsAppSettings::class)->fillForm([...$this->values(), 'base_url' => 'https://other-host.example/v1'])->call('save')->assertHasErrors(['data.base_url']);
        Livewire::test(WhatsAppSettings::class)->fillForm([...$this->values(), 'webhook_url' => 'https://localhost/hooks/cloudmsg'])->call('save')->assertHasErrors(['data.webhook_url']);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_secrets_are_cleared_even_when_form_validation_fails(): void
    {
        $this->actingAs($this->admin());
        Livewire::test(WhatsAppSettings::class)->fillForm([...$this->values(), 'base_url' => ''])->call('save')
            ->assertHasFormErrors(['base_url'])->assertSet('data.api_key', '')->assertSet('data.webhook_secret', '');
    }

    public function test_customer_suspended_admin_and_admin_without_two_factor_cannot_save(): void
    {
        foreach ([User::factory()->create(), User::factory()->create(['role' => 'admin']), User::factory()->create(['role' => 'admin', 'status' => 'suspended'])] as $user) {
            $this->actingAs($user);
            $this->assertFalse(WhatsAppSettings::canAccess());
            try {
                app(SaveCloudMsgSettingsAction::class)->execute($user, $this->values());
                $this->fail('Unauthorized settings change was accepted.');
            } catch (AuthorizationException) {
                $this->assertDatabaseMissing('settings', ['key' => CloudMsgSettings::KEY]);
            }
        }
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(EnableTwoFactorAuthentication::class)($admin, force: true);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }

    private function values(): array
    {
        return ['enabled' => true, 'base_url' => 'https://cloudmsg.impaact.io/v1', 'instance_id' => 'inst_admin123', 'admin_phone' => '+218910000099', 'webhook_url' => 'https://methaq.example/hooks/cloudmsg', 'api_key' => 'new-api-secret', 'webhook_secret' => 'new-webhook-secret'];
    }
}
