<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\DeleteLibraryAssetAction;
use App\Domains\Editor\Actions\UploadLibraryAssetAction;
use App\Domains\Editor\DTOs\EditorAssetData;
use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Exceptions\LibraryAssetException;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\EditorLibrary;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Models\User;
use App\Filament\Resources\LibraryAssets\Pages\ManageLibraryAssets;
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

final class LibraryAssetTest extends TestCase
{
    use RefreshDatabase;

    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 200"><rect width="100" height="200" fill="#203322"/></svg>';

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Library tests require the isolated MySQL test database.');
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

    public function test_an_svg_is_kept_private_with_a_png_preview_for_the_browser_and_appears_in_the_editor_library(): void
    {
        $admin = $this->administrator();

        $asset = $this->upload($admin, AssetType::Frame, 'إطار الاختبار', $this->svgFile(), $this->png(50, 100));

        $this->assertSame(AssetType::Frame, $asset->type);
        $this->assertSame('image/svg+xml', $asset->mime_type);
        $this->assertSame([50, 100], [$asset->width, $asset->height]);
        $this->assertStringEndsWith('.svg', $asset->original_path);
        $this->assertStringStartsWith('storage/editor/library/', $asset->preview_path);
        $this->assertStringEndsWith('.png', $asset->preview_path);
        Storage::disk('local')->assertExists($asset->original_path);
        Storage::disk('public')->assertExists(substr($asset->preview_path, strlen('storage/')));
        $this->assertTrue($asset->capabilities['supportsTint']);
        $this->assertSame($admin->getKey(), $asset->license_metadata['uploadedBy']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'template_asset.uploaded', 'subject_id' => $asset->getKey()]);
        $this->assertContains((string) $asset->getKey(), array_column((new EditorLibrary)->assets([]), 'id'));
    }

    public function test_a_large_picture_is_re_encoded_for_the_public_copy_while_the_original_stays_private(): void
    {
        $file = $this->png(3000, 1000);
        $bytes = (string) file_get_contents($file->getRealPath());

        $asset = $this->upload($this->administrator(), AssetType::Background, 'خلفية كبيرة', $file);

        $this->assertSame([2000, 667], [$asset->width, $asset->height]);
        $this->assertSame($bytes, Storage::disk('local')->get($asset->original_path));
        $this->assertSame([2000, 667], array_slice((array) getimagesizefromstring(Storage::disk('public')->get(substr($asset->preview_path, 8))), 0, 2));
    }

    public function test_a_font_is_served_from_its_own_public_file_and_family_names_are_unique(): void
    {
        $admin = $this->administrator();

        $asset = $this->upload($admin, AssetType::Font, 'خط جديد', $this->fontFile(), null, 'Test Family');

        $this->assertStringStartsWith('font-up-', $asset->slug);
        $url = EditorAssetData::fromModel($asset)->toArray()['fontUrl'];
        $this->assertStringStartsWith('/storage/editor/library/fonts/', (string) $url);
        $this->assertSame('Test Family', EditorAssetData::fromModel($asset)->toArray()['fontFamily']);

        $this->expectException(ValidationException::class);
        $this->upload($admin, AssetType::Font, 'خط آخر', $this->fontFile(), null, 'Test Family');
    }

    public function test_unsafe_or_incomplete_uploads_are_refused_and_nothing_is_kept(): void
    {
        $admin = $this->administrator();
        $script = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><script>alert(1)</script></svg>');
        $external = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10"><image xlink:href="https://evil.test/a.png"/></svg>');
        $disguised = UploadedFile::fake()->createWithContent('x.png', '<?php echo 1;');
        $notAFont = UploadedFile::fake()->createWithContent('x.ttf', 'plain text, not a font');

        $attempts = [
            fn () => $this->upload($admin, AssetType::Frame, 'a', $script, $this->png(10, 10)),
            fn () => $this->upload($admin, AssetType::Frame, 'a', $external, $this->png(10, 10)),
            fn () => $this->upload($admin, AssetType::Frame, 'a', $this->svgFile(), null),
            fn () => $this->upload($admin, AssetType::Frame, 'a', $this->svgFile(), $this->png(100, 100)),
            fn () => $this->upload($admin, AssetType::Frame, 'a', $disguised),
            fn () => $this->upload($admin, AssetType::Font, 'a', $notAFont, null, 'Some Font'),
            fn () => $this->upload($admin, AssetType::Audio, 'a', $this->png(10, 10)),
            fn () => $this->upload($admin, AssetType::Frame, 'a', $this->png(10, 10), null, null, false),
            fn () => $this->upload($admin, AssetType::Frame, '', $this->png(10, 10)),
        ];

        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('An invalid upload was accepted.');
            } catch (ValidationException) {
                // expected
            }
        }

        $this->assertSame(0, TemplateAsset::query()->where('license_metadata->uploadedBy', $admin->getKey())->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_customers_cannot_upload_delete_or_open_the_library_page(): void
    {
        $customer = User::factory()->create();
        $asset = $this->upload($this->administrator(), AssetType::Icon, 'أيقونة', $this->png(10, 10));

        $this->actingAs($customer)->get('/admin/library-assets')->assertForbidden();

        foreach ([
            fn () => $this->upload($customer, AssetType::Icon, 'x', $this->png(10, 10)),
            fn () => app(DeleteLibraryAssetAction::class)->execute($customer, (int) $asset->getKey()),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('A customer changed the library.');
            } catch (AuthorizationException) {
                // expected
            }
        }
        $this->assertDatabaseHas('template_assets', ['id' => $asset->getKey()]);
    }

    public function test_an_unused_uploaded_asset_is_deleted_with_its_files(): void
    {
        $admin = $this->administrator();
        $asset = $this->upload($admin, AssetType::Decoration, 'زخرفة', $this->png(10, 10));
        $original = $asset->original_path;
        $public = substr($asset->preview_path, strlen('storage/'));

        app(DeleteLibraryAssetAction::class)->execute($admin, (int) $asset->getKey());

        $this->assertDatabaseMissing('template_assets', ['id' => $asset->getKey()]);
        Storage::disk('local')->assertMissing($original);
        Storage::disk('public')->assertMissing($public);
        $this->assertDatabaseHas('audit_logs', ['action' => 'template_asset.deleted', 'subject_id' => $asset->getKey()]);
    }

    public function test_an_asset_a_design_uses_and_a_methaq_asset_are_never_deleted(): void
    {
        $admin = $this->administrator();
        $used = $this->upload($admin, AssetType::Decoration, 'مستعملة', $this->png(10, 10));
        $event = Event::factory()->create();
        EventDesign::factory()->create(['event_id' => $event->getKey(), 'design_json' => ['width' => 1080, 'height' => 1920, 'layers' => [['type' => 'image', 'asset' => ['assetId' => (string) $used->getKey(), 'version' => 1]]]]]);
        $base = TemplateAsset::factory()->create();

        foreach ([$used, $base] as $asset) {
            try {
                app(DeleteLibraryAssetAction::class)->execute($admin, (int) $asset->getKey());
                $this->fail('A protected asset was deleted.');
            } catch (LibraryAssetException) {
                $this->assertDatabaseHas('template_assets', ['id' => $asset->getKey()]);
            }
        }
        $this->assertDatabaseMissing('audit_logs', ['action' => 'template_asset.deleted']);
    }

    public function test_the_admin_page_lists_uploads_and_hides_or_deletes_them(): void
    {
        $admin = $this->administrator();
        $asset = $this->upload($admin, AssetType::Frame, 'إطار', $this->png(10, 10));
        $this->actingAs($admin);

        Livewire::test(ManageLibraryAssets::class)
            ->assertCanSeeTableRecords([$asset])
            ->callAction(TestAction::make('status')->table($asset), ['reason' => 'جودة'])
            ->callAction(TestAction::make('remove')->table($asset));

        $this->assertDatabaseMissing('template_assets', ['id' => $asset->getKey()]);
    }

    private function upload(User $admin, AssetType $type, string $name, UploadedFile $file, ?UploadedFile $preview = null, ?string $family = null, bool $rights = true): TemplateAsset
    {
        return app(UploadLibraryAssetAction::class)->execute($admin, $type, $name, $file, $preview, 'تصميم ميثاق', $rights, $family);
    }

    private function svgFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('frame.svg', self::SVG);
    }

    private function fontFile(): UploadedFile
    {
        return new UploadedFile(public_path('brand/fonts/AmiriQuran-Regular.ttf'), 'font.ttf', 'font/ttf', null, true);
    }

    private function png(int $width, int $height): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 32, 51, 34));
        $path = tempnam(sys_get_temp_dir(), 'png');
        imagepng($image, $path);

        return new UploadedFile($path, 'image.png', 'image/png', null, true);
    }

    private function administrator(): User
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $admin->fresh();
    }
}
