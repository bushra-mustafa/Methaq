<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\ListFeaturedTemplatesAction;
use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Users\Models\User;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
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
        Template::query()->update(['price_minor' => 25_000]);
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->update(['price_minor' => 25_000]);

        $this->assertDatabaseCount('templates', 16);
        $this->assertDatabaseCount('template_assets', 56);
        $this->assertDatabaseCount('asset_collections', 11);

        TemplateAsset::query()->where('type', '!=', 'audio')->each(function (TemplateAsset $asset): void {
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
                $this->assertSame($asset->mime_type === 'image/svg+xml' ? 'tintable' : 'fixed', $asset->metadata['colorMode']);
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
        Template::query()->update(['price_minor' => 25_000]);
        $assets = TemplateAsset::query()->get()->keyBy(static fn (TemplateAsset $asset): string => (string) $asset->getKey());

        Template::query()->each(function (Template $template) use ($assets): void {
            $this->assertSame(1, $template->schema_version);
            $this->assertSame(1080, $template->default_design_json['width']);
            $this->assertSame(1920, $template->default_design_json['height']);

            foreach ($template->default_design_json['layers'] as $layer) {
                if ($layer['type'] === 'shape') {
                    continue;
                }

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
        Template::query()->update(['price_minor' => 25_000]);
        Template::query()->where('slug', 'burgundy-noir')->update(['is_active' => false]);
        TemplateAsset::query()->where('slug', 'wax-seal')->update(['is_active' => false]);

        $response = $this->withoutVite()->get('/templates');

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Web/Templates', false)
            ->has('templates', 15)
            ->has('collections', 11)
            ->has('categories', 6)
            ->where('selectedCategory', null));

        $serialized = $response->getContent();
        $this->assertStringNotContainsString('original_path', $serialized);
        $this->assertStringNotContainsString('editor/assets/v1/', $serialized);
        $this->assertStringNotContainsString('default_design_json', $serialized);
        $this->assertStringNotContainsString('wax-seal', $serialized);

        $this->withoutVite()->get('/templates?category=graduation')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('templates', 2)
                ->where('templates.0.slug', 'graduation-emerald')
                ->where('templates.1.slug', 'graduation-navy')
                ->where('selectedCategory', 'graduation'));

        $this->withoutVite()->get('/templates?category=unknown')->assertSessionHasErrors('category');
    }

    public function test_henna_noir_template_opens_in_the_animated_scene_with_its_music(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->update(['price_minor' => 25_000]);
        $template = Template::query()->where('slug', 'henna-noir')->firstOrFail();
        $audio = TemplateAsset::query()->where('slug', 'audio-henna-celebration')->firstOrFail();

        $this->assertSame('henna', $template->category->value);
        $this->assertSame('henna-noir', $template->default_scene_json['backdrop']['preset']);
        $this->assertSame('envelope', $template->default_scene_json['opening']['type']);
        $this->assertTrue($template->default_scene_json['audio']['enabled']);
        $this->assertSame((string) $audio->getKey(), $template->default_scene_json['audio']['asset']['assetId']);
        $this->assertSame('audio/mpeg', $audio->mime_type);
        $this->assertTrue($audio->license_metadata['commercialUse']);
        $this->assertNotEmpty(array_filter(
            $template->default_design_json['layers'],
            static fn (array $layer): bool => $layer['type'] === 'text' && $layer['locked'] === false,
        ));
    }

    /** @return array<string, array{string, string}> */
    public static function templatePackages(): array
    {
        return [
            'jasmine' => ['jasmine-white', 'jasmine'],
            'mediterranean' => ['mediterranean-blue', 'mediterranean'],
            'ghadames' => ['ghadames-terracotta', 'ghadames'],
            'lavender' => ['lavender-silver', 'lavender'],
        ];
    }

    #[DataProvider('templatePackages')]
    public function test_each_theme_template_recommends_its_whole_package_and_has_a_ready_set(string $templateSlug, string $family): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->update(['price_minor' => 25_000]);

        $template = Template::query()->where('slug', $templateSlug)->with('assets')->sole();
        $packageAssets = $template->assets->filter(fn (TemplateAsset $asset): bool => ($asset->metadata['family'] ?? null) === $family);
        $this->assertCount(4, $packageAssets, 'frame, corner, divider and medallion are recommended together');
        $this->assertEqualsCanonicalizing(['frame', 'decoration', 'decoration', 'icon'], $packageAssets->map(fn (TemplateAsset $asset): string => $asset->type->value)->values()->all());

        $set = AssetCollection::query()->where('slug', "{$templateSlug}-set")->with('items.asset')->sole();
        $this->assertNotEmpty($set->items);
        $this->assertTrue($set->items->every(fn ($item): bool => $packageAssets->contains('id', $item->asset->getKey())));
    }

    public function test_collections_keep_typed_placement_for_each_item(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->update(['price_minor' => 25_000]);

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
        Template::query()->update(['price_minor' => 25_000]);

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

    public function test_templates_without_a_price_are_hidden_everywhere_and_cannot_start_an_event(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        Template::query()->update(['price_minor' => 25_000]);
        $hidden = Template::query()->where('slug', 'powder-gold')->sole();
        $hidden->forceFill(['price_minor' => 0])->save();

        $this->withoutVite()->get('/templates')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('templates', 15)
            ->where('templates', fn ($templates): bool => ! collect($templates)->contains(fn (array $template): bool => $template['slug'] === 'powder-gold')));

        $this->assertNotContains('powder-gold', array_column(app(ListFeaturedTemplatesAction::class)->execute(), 'slug'));

        $this->actingAs(User::factory()->create())
            ->post('/app/events', [
                'title' => 'حفل',
                'category' => 'wedding',
                'template_id' => $hidden->getKey(),
                'event_date' => '2027-03-26T12:00',
                'timezone' => 'Africa/Tripoli',
            ])
            ->assertSessionHasErrors('template_id');
    }
}
