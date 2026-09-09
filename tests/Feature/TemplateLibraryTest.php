<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class TemplateLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Template library integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_library_seed_is_idempotent_and_keeps_originals_private(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $this->seed(TemplateLibrarySeeder::class);

        $this->assertDatabaseCount('templates', 4);
        $this->assertDatabaseCount('template_assets', 17);
        $this->assertDatabaseCount('asset_collections', 4);

        TemplateAsset::query()->each(function (TemplateAsset $asset): void {
            $this->assertTrue(Storage::disk('local')->exists($asset->original_path));
            $this->assertFileExists(public_path($asset->preview_path));
            $this->assertStringStartsWith('editor/assets/v1/', $asset->original_path);
            $this->assertStringStartsWith('editor/previews/', $asset->preview_path);
            $this->assertNotEmpty($asset->license_metadata);
            $this->assertNotEmpty($asset->capabilities);

            if ($asset->type->value !== 'font') {
                $this->assertEqualsCanonicalizing(
                    ['sourceProject', 'neutralExample', 'family', 'category', 'placement', 'orientation', 'style', 'colorMode'],
                    array_keys($asset->metadata),
                );
                $this->assertSame('tintable', $asset->metadata['colorMode']);
                $this->assertStringStartsWith('editor/previews/assets/v1/', $asset->preview_path);
            }
        });

        $seededCopy = Template::query()
            ->get()
            ->flatMap(static fn (Template $template): array => [
                $template->name,
                json_encode($template->default_design_json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                json_encode($template->default_scene_json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ])
            ->implode(' ');

        $this->assertDoesNotMatchRegularExpression('/hussen|bushra|anas|lamar/i', $seededCopy);
    }

    public function test_template_documents_reference_versioned_library_assets(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $assets = TemplateAsset::query()->get()->keyBy(static fn (TemplateAsset $asset): string => (string) $asset->getKey());

        Template::query()->each(function (Template $template) use ($assets): void {
            $this->assertSame(1, $template->schema_version);
            $this->assertSame(1080, $template->default_design_json['width']);
            $this->assertSame(1920, $template->default_design_json['height']);

            foreach ($template->default_design_json['layers'] as $layer) {
                $reference = $layer['type'] === 'text' ? $layer['font'] : $layer['asset'];
                $asset = $assets->get($reference['assetId']);

                $this->assertInstanceOf(TemplateAsset::class, $asset);
                $this->assertSame($asset->asset_version, $reference['version']);
            }
        });
    }

    public function test_public_library_exposes_only_active_previews_and_supports_category_filtering(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->where('slug', 'burgundy-noir')->update(['is_active' => false]);
        TemplateAsset::query()->where('slug', 'wax-seal')->update(['is_active' => false]);

        $response = $this->withoutVite()->get('/templates');

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Web/Templates', false)
            ->has('templates', 3)
            ->has('collections', 4)
            ->has('categories', 4)
            ->where('selectedCategory', null));

        $serialized = $response->getContent();
        $this->assertStringNotContainsString('original_path', $serialized);
        $this->assertStringNotContainsString('editor/assets/v1/', $serialized);
        $this->assertStringNotContainsString('default_design_json', $serialized);
        $this->assertStringNotContainsString('wax-seal', $serialized);

        $this->withoutVite()->get('/templates?category=graduation')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('templates', 1)
                ->where('templates.0.slug', 'graduation-emerald')
                ->where('selectedCategory', 'graduation'));

        $this->withoutVite()->get('/templates?category=unknown')->assertSessionHasErrors('category');
    }

    public function test_collections_keep_typed_placement_for_each_item(): void
    {
        $this->seed(TemplateLibrarySeeder::class);

        AssetCollection::query()->with('items')->each(function (AssetCollection $collection): void {
            $this->assertNotEmpty($collection->items);
            foreach ($collection->items as $item) {
                $this->assertEqualsCanonicalizing(
                    ['x', 'y', 'width', 'height', 'scaleX', 'scaleY', 'rotation', 'opacity', 'locked'],
                    array_keys($item->placement_json),
                );
            }
        });
    }

    public function test_original_islamic_ornaments_are_owned_and_available_as_a_ready_collection(): void
    {
        $this->seed(TemplateLibrarySeeder::class);

        $ornaments = TemplateAsset::query()
            ->whereJsonContains('metadata->sourceProject', 'methaq-original-islamic-ornaments')
            ->get();

        $this->assertCount(5, $ornaments);
        $ornaments->each(function (TemplateAsset $asset): void {
            $this->assertSame('Methaq proprietary', $asset->license_metadata['license']);
            $this->assertSame('Methaq Design System', $asset->license_metadata['source']);
            $this->assertTrue($asset->license_metadata['commercialUse']);
        });

        $collection = AssetCollection::query()
            ->with('items.asset')
            ->where('slug', 'methaq-islamic-ornament-set')
            ->sole();

        $this->assertCount(4, $collection->items);
        $this->assertEqualsCanonicalizing(
            [
                'methaq-pointed-arch-frame',
                'methaq-eight-star-medallion',
                'methaq-diamond-divider',
                'methaq-geometric-side-border',
            ],
            $collection->items->pluck('asset.slug')->all(),
        );
    }
}
