<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\UploadAudioAssetAction;
use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\EditorLibrary;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\AudioTracks\Pages\ManageAudioTracks;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Livewire;
use Tests\TestCase;

final class AudioUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Audio upload tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        Filament::setCurrentPanel('admin');
    }

    public function test_an_administrator_uploads_a_track_that_every_designer_can_then_pick(): void
    {
        $admin = $this->administrator();

        $asset = app(UploadAudioAssetAction::class)->execute($admin, 'نغمة الفرح', $this->realWav(), 'تأليف ميثاق', true);

        $this->assertSame(AssetType::Audio, $asset->type);
        $this->assertContains($asset->mime_type, ['audio/wav', 'audio/x-wav']);
        $this->assertStringStartsWith('storage/editor/audio/', $asset->preview_path);
        $this->assertStringEndsWith('.wav', $asset->preview_path);
        Storage::disk('public')->assertExists(substr($asset->preview_path, strlen('storage/')));
        Storage::disk('local')->assertExists($asset->original_path);
        $this->assertSame(['source' => 'تأليف ميثاق', 'rightsConfirmed' => true, 'uploadedBy' => $admin->getKey()], $asset->license_metadata);
        $this->assertDatabaseHas('audit_logs', ['action' => 'template_asset.uploaded', 'subject_id' => $asset->getKey()]);
        $this->assertContains((string) $asset->getKey(), array_column((new EditorLibrary)->assets([]), 'id'));
    }

    public function test_a_disguised_or_oversized_file_and_missing_declarations_are_refused_and_nothing_is_kept(): void
    {
        $admin = $this->administrator();
        $action = app(UploadAudioAssetAction::class);
        $script = UploadedFile::fake()->createWithContent('song.mp3', '<?php echo 1;');
        $huge = UploadedFile::fake()->create('big.mp3', 11 * 1024);

        foreach ([
            fn () => $action->execute($admin, 'ملف', $script, 'مصدر', true),
            fn () => $action->execute($admin, 'ملف', $huge, 'مصدر', true),
            fn () => $action->execute($admin, 'ملف', $this->realWav(), 'مصدر', false),
            fn () => $action->execute($admin, 'ملف', $this->realWav(), '  ', true),
            fn () => $action->execute($admin, '', $this->realWav(), 'مصدر', true),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('An invalid upload was accepted.');
            } catch (ValidationException) {
                // expected
            }
        }

        $this->assertSame(0, TemplateAsset::query()->where('type', 'audio')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_customers_cannot_upload_or_open_the_audio_page(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/audio-tracks')->assertForbidden();

        $this->expectException(AuthorizationException::class);
        app(UploadAudioAssetAction::class)->execute($customer, 'نغمة', $this->realWav(), 'مصدر', true);
    }

    public function test_the_admin_page_lists_tracks_and_hides_one_with_a_reason_without_deleting_it(): void
    {
        $admin = $this->administrator();
        $asset = app(UploadAudioAssetAction::class)->execute($admin, 'نغمة', $this->realWav(), 'مصدر', true);
        $this->actingAs($admin);

        Livewire::test(ManageAudioTracks::class)
            ->assertCanSeeTableRecords([$asset])
            ->callAction(TestAction::make('status')->table($asset), ['reason' => 'جودة منخفضة']);

        $this->assertFalse($asset->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'template_asset.deactivated', 'subject_id' => $asset->getKey()]);
        $this->assertNotContains((string) $asset->getKey(), array_column((new EditorLibrary)->assets([]), 'id'));
        $this->assertContains((string) $asset->getKey(), array_column((new EditorLibrary)->assets([(int) $asset->getKey()]), 'id'));
    }

    private function realWav(): UploadedFile
    {
        return new UploadedFile(public_path('brand/audio/methaq-chime.wav'), 'chime.wav', 'audio/wav', null, true);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
