<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Editor\Enums\AssetType;
use App\Domains\Editor\Enums\TemplatePreset;
use App\Domains\Editor\Models\AssetCollection;
use App\Domains\Editor\Models\AssetCollectionItem;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\SvgAssetValidator;
use App\Domains\Editor\Services\TemplateDesignFactory;
use App\Domains\Events\Enums\EventCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class TemplateLibrarySeeder extends Seeder
{
    public function __construct(
        private readonly SvgAssetValidator $svgValidator,
        private readonly TemplateDesignFactory $designFactory,
    ) {}

    public function run(): void
    {
        $assets = $this->seedAssets();
        $this->seedTemplates($assets);
        $this->seedCollections($assets);
    }

    /** @return array<string, TemplateAsset> */
    private function seedAssets(): array
    {
        $assets = [];

        foreach ($this->assetDefinitions() as $definition) {
            $this->assertAssetFilesExist($definition['original_path'], $definition['preview_path'], $definition['mime_type']);

            $asset = TemplateAsset::query()->where('slug', $definition['slug'])->first() ?? new TemplateAsset;
            $isNew = ! $asset->exists;
            $asset->forceFill($definition + ($isNew ? ['is_active' => true] : []))->save();
            $assets[$asset->slug] = $asset;
        }

        return $assets;
    }

    /** @param array<string, TemplateAsset> $assets */
    private function seedTemplates(array $assets): void
    {
        foreach ($this->templateDefinitions() as $definition) {
            $document = $this->designFactory->make($definition['preset'], $assets);
            $columns = $document->toStorageColumns();
            $template = Template::query()->where('slug', $definition['slug'])->first() ?? new Template;
            $isNew = ! $template->exists;
            $template->forceFill([
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'category' => $definition['category'],
                'thumbnail_path' => $definition['thumbnail_path'],
                'default_design_json' => $columns['design_json'],
                'default_scene_json' => $columns['scene_json'],
                'default_palette_json' => $columns['palette_json'],
                'schema_version' => $columns['schema_version'],
                ...($isNew ? ['is_active' => true] : []),
            ])->save();

            $links = [];
            foreach ($definition['assets'] as $sortOrder => $slug) {
                $links[$assets[$slug]->getKey()] = ['sort_order' => $sortOrder];
            }
            $template->assets()->sync($links);
        }
    }

    /** @param array<string, TemplateAsset> $assets */
    private function seedCollections(array $assets): void
    {
        foreach ($this->collectionDefinitions() as $definition) {
            $collection = AssetCollection::query()->where('slug', $definition['slug'])->first() ?? new AssetCollection;
            $isNew = ! $collection->exists;
            $collection->forceFill([
                'slug' => $definition['slug'],
                'name' => $definition['name'],
                'thumbnail_path' => $definition['thumbnail_path'],
                ...($isNew ? ['is_active' => true] : []),
            ])->save();

            $collection->items()->delete();
            foreach ($definition['items'] as $sortOrder => $itemDefinition) {
                $item = new AssetCollectionItem;
                $item->forceFill([
                    'collection_id' => $collection->getKey(),
                    'template_asset_id' => $assets[$itemDefinition['asset']]->getKey(),
                    'sort_order' => $sortOrder,
                    'placement_json' => $itemDefinition['placement'],
                ])->save();
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function assetDefinitions(): array
    {
        $visualLicense = [
            'license' => 'Methaq proprietary',
            'source' => 'Methaq Design System',
            'commercialUse' => true,
        ];
        $visualCapabilities = [
            'supportsTint' => true,
            'supportsOpacity' => true,
            'allowedPlacements' => ['canvas', 'collection'],
        ];

        return [
            $this->visualAsset('powder-botanical-frame', 'إطار نباتي بودري', AssetType::Frame, 'frames/powder-botanical-frame.svg', 'templates/powder-gold.svg', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('burgundy-arch-frame', 'إطار قوس عنابي', AssetType::Frame, 'frames/burgundy-arch-frame.svg', 'templates/burgundy-noir.svg', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('heritage-geometric-frame', 'إطار تراثي هندسي', AssetType::Frame, 'frames/heritage-geometric-frame.svg', 'templates/heritage-vertical.svg', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('graduation-rays-frame', 'إطار أشعة التخرج', AssetType::Frame, 'frames/graduation-rays-frame.svg', 'templates/graduation-emerald.svg', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('botanical-corner-left', 'غصن نباتي يسار', AssetType::Decoration, 'decorations/botanical-corner-left.svg', 'collections/botanical-set.svg', 480, 700, $visualCapabilities, $visualLicense),
            $this->visualAsset('botanical-corner-right', 'غصن نباتي يمين', AssetType::Decoration, 'decorations/botanical-corner-right.svg', 'collections/botanical-set.svg', 480, 700, $visualCapabilities, $visualLicense),
            $this->visualAsset('gold-divider', 'فاصل ذهبي', AssetType::Decoration, 'decorations/gold-divider.svg', 'collections/botanical-set.svg', 600, 80, $visualCapabilities, $visualLicense),
            $this->visualAsset('wax-seal', 'ختم شمعي', AssetType::Icon, 'decorations/wax-seal.svg', 'collections/heritage-set.svg', 320, 320, $visualCapabilities, $visualLicense),
            $this->fontAsset('font-amiri', 'أميري', 'Amiri-Regular.ttf', 'Amiri', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-pinyon', 'Pinyon Script', 'PinyonScript-Regular.ttf', 'Pinyon Script', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-noto-arabic', 'Noto Sans Arabic', 'NotoSansArabic.ttf', 'Noto Sans Arabic', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-manrope', 'Manrope', 'Manrope.ttf', 'Manrope', 'SIL Open Font License 1.1'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function templateDefinitions(): array
    {
        return [
            [
                'slug' => TemplatePreset::PowderGold->value,
                'name' => 'بودري ولمعة ذهبية',
                'category' => EventCategory::Wedding,
                'thumbnail_path' => 'editor/previews/templates/powder-gold.svg',
                'preset' => TemplatePreset::PowderGold,
                'assets' => ['powder-botanical-frame', 'gold-divider', 'font-amiri', 'font-pinyon', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::BurgundyNoir->value,
                'name' => 'عنابي ملكي',
                'category' => EventCategory::Henna,
                'thumbnail_path' => 'editor/previews/templates/burgundy-noir.svg',
                'preset' => TemplatePreset::BurgundyNoir,
                'assets' => ['burgundy-arch-frame', 'gold-divider', 'font-amiri', 'font-pinyon', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::HeritageVertical->value,
                'name' => 'ميثاق تراثي عمودي',
                'category' => EventCategory::MarriageContract,
                'thumbnail_path' => 'editor/previews/templates/heritage-vertical.svg',
                'preset' => TemplatePreset::HeritageVertical,
                'assets' => ['heritage-geometric-frame', 'gold-divider', 'wax-seal', 'font-amiri', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::GraduationEmerald->value,
                'name' => 'تخرج زمردي',
                'category' => EventCategory::Graduation,
                'thumbnail_path' => 'editor/previews/templates/graduation-emerald.svg',
                'preset' => TemplatePreset::GraduationEmerald,
                'assets' => ['graduation-rays-frame', 'gold-divider', 'font-amiri', 'font-pinyon', 'font-noto-arabic', 'font-manrope'],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function collectionDefinitions(): array
    {
        return [
            [
                'slug' => 'botanical-gold-set',
                'name' => 'أغصان بودرية وفاصل ذهبي',
                'thumbnail_path' => 'editor/previews/collections/botanical-set.svg',
                'items' => [
                    ['asset' => 'botanical-corner-left', 'placement' => $this->placement(0, 0, 360, 525, 0.9)],
                    ['asset' => 'botanical-corner-right', 'placement' => $this->placement(720, 1395, 360, 525, 0.9)],
                    ['asset' => 'gold-divider', 'placement' => $this->placement(240, 1115, 600, 80, 1)],
                ],
            ],
            [
                'slug' => 'heritage-seal-set',
                'name' => 'إطار تراثي وختم',
                'thumbnail_path' => 'editor/previews/collections/heritage-set.svg',
                'items' => [
                    ['asset' => 'heritage-geometric-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'wax-seal', 'placement' => $this->placement(420, 1480, 240, 240, 1)],
                ],
            ],
            [
                'slug' => 'graduation-gold-set',
                'name' => 'قوس التخرج والفاصل',
                'thumbnail_path' => 'editor/previews/collections/graduation-set.svg',
                'items' => [
                    ['asset' => 'graduation-rays-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'gold-divider', 'placement' => $this->placement(240, 1305, 600, 80, 1)],
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $capabilities @param array<string, mixed> $license */
    private function visualAsset(string $slug, string $name, AssetType $type, string $original, string $preview, int $width, int $height, array $capabilities, array $license): array
    {
        return [
            'slug' => $slug,
            'name' => $name,
            'type' => $type,
            'original_path' => 'editor/assets/v1/'.$original,
            'preview_path' => 'editor/previews/'.$preview,
            'mime_type' => 'image/svg+xml',
            'width' => $width,
            'height' => $height,
            'metadata' => ['sourceProject' => 'methaq-reference-directions', 'neutralExample' => true],
            'capabilities' => $capabilities,
            'license_metadata' => $license,
            'asset_version' => 1,
        ];
    }

    /** @return array<string, mixed> */
    private function fontAsset(string $slug, string $name, string $filename, string $family, string $license): array
    {
        return [
            'slug' => $slug,
            'name' => $name,
            'type' => AssetType::Font,
            'original_path' => 'editor/assets/v1/fonts/'.$filename,
            'preview_path' => 'editor/previews/assets/font-sample.svg',
            'mime_type' => 'font/ttf',
            'width' => null,
            'height' => null,
            'metadata' => ['fontFamily' => $family, 'format' => 'truetype'],
            'capabilities' => ['languages' => str_contains($slug, 'pinyon') || str_contains($slug, 'manrope') ? ['en'] : ['ar', 'en']],
            'license_metadata' => ['license' => $license, 'redistribution' => true],
            'asset_version' => 1,
        ];
    }

    /** @return array{x: int, y: int, width: int, height: int, scaleX: int, scaleY: int, rotation: int, opacity: float, locked: bool} */
    private function placement(int $x, int $y, int $width, int $height, float $opacity): array
    {
        return [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'scaleX' => 1,
            'scaleY' => 1,
            'rotation' => 0,
            'opacity' => $opacity,
            'locked' => false,
        ];
    }

    private function assertAssetFilesExist(string $originalPath, string $previewPath, string $mimeType): void
    {
        if (! Storage::disk('local')->exists($originalPath)) {
            throw new RuntimeException("Missing private library asset: {$originalPath}");
        }
        if (! is_file(public_path($previewPath))) {
            throw new RuntimeException("Missing public library preview: {$previewPath}");
        }

        if (str_ends_with($previewPath, '.svg')) {
            $previewContents = file_get_contents(public_path($previewPath));
            if (! is_string($previewContents)) {
                throw new RuntimeException("Unable to read public library preview: {$previewPath}");
            }
            $this->svgValidator->assertSafe($previewContents);
        }

        if ($mimeType === 'image/svg+xml') {
            $contents = Storage::disk('local')->get($originalPath);
            $this->svgValidator->assertSafe($contents);
        }
    }
}
