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
        $this->call(InvitationAudioSeeder::class);
        $assets = $this->seedAssets();
        $assets[InvitationAudioSeeder::HENNA_CELEBRATION_SLUG] = TemplateAsset::query()
            ->where('slug', InvitationAudioSeeder::HENNA_CELEBRATION_SLUG)
            ->firstOrFail();
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
        foreach ($this->templateDefinitions() as $position => $definition) {
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
                // Visibility, order, price and locked features belong to the administrator after the first seed.
                ...($isNew ? ['is_active' => true, 'is_featured' => true, 'sort_order' => $position + 1] : []),
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

        $rasterCapabilities = [
            'supportsTint' => false,
            'supportsOpacity' => true,
            'allowedPlacements' => ['canvas', 'collection'],
        ];
        $ivoryLicense = [
            'license' => 'Methaq proprietary',
            'source' => 'Generated for Methaq with imagegen (see public/editor/proposals/ivory-wedding/v1/sources.json)',
            'commercialUse' => true,
        ];

        return [
            $this->visualAsset('powder-botanical-frame', 'إطار نباتي بودري', AssetType::Frame, 'frames/botanical/powder-botanical-frame.svg', 'assets/v1/frames/botanical/powder-botanical-frame.png', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('burgundy-arch-frame', 'إطار قوس عنابي', AssetType::Frame, 'frames/arches/burgundy-arch-frame.svg', 'assets/v1/frames/arches/burgundy-arch-frame.png', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('heritage-geometric-frame', 'إطار تراثي هندسي', AssetType::Frame, 'frames/geometric/heritage-geometric-frame.svg', 'assets/v1/frames/geometric/heritage-geometric-frame.png', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('graduation-rays-frame', 'إطار أشعة التخرج', AssetType::Frame, 'frames/graduation/graduation-rays-frame.svg', 'assets/v1/frames/graduation/graduation-rays-frame.png', 1080, 1920, $visualCapabilities, $visualLicense),
            $this->visualAsset('botanical-corner-left', 'غصن نباتي يسار', AssetType::Decoration, 'ornaments/botanical/botanical-corner-left.svg', 'assets/v1/ornaments/botanical/botanical-corner-left.png', 480, 700, $visualCapabilities, $visualLicense),
            $this->visualAsset('botanical-corner-right', 'غصن نباتي يمين', AssetType::Decoration, 'ornaments/botanical/botanical-corner-right.svg', 'assets/v1/ornaments/botanical/botanical-corner-right.png', 480, 700, $visualCapabilities, $visualLicense),
            $this->visualAsset('gold-divider', 'فاصل ذهبي', AssetType::Decoration, 'ornaments/dividers/gold-divider.svg', 'assets/v1/ornaments/dividers/gold-divider.png', 600, 80, $visualCapabilities, $visualLicense),
            $this->visualAsset('wax-seal', 'ختم شمعي', AssetType::Icon, 'seals/wax/wax-seal.svg', 'assets/v1/seals/wax/wax-seal.png', 320, 320, $visualCapabilities, $visualLicense),
            $this->visualAsset('methaq-pointed-arch-frame', 'إطار محراب ميثاق', AssetType::Frame, 'frames/arches/methaq-pointed-arch-frame.svg', 'assets/v1/frames/arches/methaq-pointed-arch-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-original-islamic-ornaments'),
            $this->visualAsset('methaq-arabesque-corner', 'زاوية أرابيسك ميثاق', AssetType::Decoration, 'ornaments/corners/methaq-arabesque-corner.svg', 'assets/v1/ornaments/corners/methaq-arabesque-corner.png', 480, 480, $visualCapabilities, $visualLicense, 'methaq-original-islamic-ornaments'),
            $this->visualAsset('methaq-eight-star-medallion', 'ميدالية النجمة الثمانية', AssetType::Icon, 'ornaments/medallions/methaq-eight-star-medallion.svg', 'assets/v1/ornaments/medallions/methaq-eight-star-medallion.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-original-islamic-ornaments'),
            $this->visualAsset('methaq-diamond-divider', 'فاصل المعين الإسلامي', AssetType::Decoration, 'ornaments/dividers/methaq-diamond-divider.svg', 'assets/v1/ornaments/dividers/methaq-diamond-divider.png', 720, 120, $visualCapabilities, $visualLicense, 'methaq-original-islamic-ornaments'),
            $this->visualAsset('methaq-geometric-side-border', 'شريط هندسي إسلامي', AssetType::Decoration, 'ornaments/borders/vertical/methaq-geometric-side-border.svg', 'assets/v1/ornaments/borders/vertical/methaq-geometric-side-border.png', 180, 1200, $visualCapabilities, $visualLicense, 'methaq-original-islamic-ornaments'),
            $this->fontAsset('font-amiri', 'أميري', 'arabic/Amiri-Regular.ttf', 'Amiri', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-pinyon', 'Pinyon Script', 'latin/PinyonScript-Regular.ttf', 'Pinyon Script', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-noto-arabic', 'Noto Sans Arabic', 'arabic/NotoSansArabic.ttf', 'Noto Sans Arabic', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-manrope', 'Manrope', 'latin/Manrope.ttf', 'Manrope', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-cormorant', 'Cormorant Garamond', 'latin/CormorantGaramond.ttf', 'Cormorant Garamond', 'SIL Open Font License 1.1'),
            $this->fontAsset('font-aref-ruqaa', 'عارف رقعة', 'arabic/ArefRuqaa-Regular.ttf', 'Aref Ruqaa', 'SIL Open Font License 1.1'),
            $this->visualAsset('jasmine-white-frame', 'إطار الياسمين الأبيض', AssetType::Frame, 'frames/jasmine/jasmine-white-frame.svg', 'assets/v1/frames/jasmine/jasmine-white-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('jasmine-corner', 'زاوية ياسمين', AssetType::Decoration, 'ornaments/corners/jasmine-corner.svg', 'assets/v1/ornaments/corners/jasmine-corner.png', 480, 480, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('jasmine-divider', 'فاصل الياسمين', AssetType::Decoration, 'ornaments/dividers/jasmine-divider.svg', 'assets/v1/ornaments/dividers/jasmine-divider.png', 720, 120, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('jasmine-wreath', 'إكليل الياسمين', AssetType::Icon, 'ornaments/medallions/jasmine-wreath.svg', 'assets/v1/ornaments/medallions/jasmine-wreath.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('mediterranean-arch-frame', 'إطار قوس المتوسط', AssetType::Frame, 'frames/arches/mediterranean-arch-frame.svg', 'assets/v1/frames/arches/mediterranean-arch-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('mediterranean-tile-corner', 'زاوية بلاط متوسطي', AssetType::Decoration, 'ornaments/corners/mediterranean-tile-corner.svg', 'assets/v1/ornaments/corners/mediterranean-tile-corner.png', 480, 480, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('mediterranean-wave-divider', 'فاصل الموج', AssetType::Decoration, 'ornaments/dividers/mediterranean-wave-divider.svg', 'assets/v1/ornaments/dividers/mediterranean-wave-divider.png', 720, 120, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('mediterranean-star-medallion', 'ميدالية النجمة المتوسطية', AssetType::Icon, 'ornaments/medallions/mediterranean-star-medallion.svg', 'assets/v1/ornaments/medallions/mediterranean-star-medallion.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('ghadames-wall-frame', 'إطار جدار غدامس', AssetType::Frame, 'frames/geometric/ghadames-wall-frame.svg', 'assets/v1/frames/geometric/ghadames-wall-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('ghadames-corner', 'زاوية غدامسية', AssetType::Decoration, 'ornaments/corners/ghadames-corner.svg', 'assets/v1/ornaments/corners/ghadames-corner.png', 480, 480, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('ghadames-divider', 'فاصل غدامسي', AssetType::Decoration, 'ornaments/dividers/ghadames-divider.svg', 'assets/v1/ornaments/dividers/ghadames-divider.png', 720, 120, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('ghadames-medallion', 'ميدالية غدامس', AssetType::Icon, 'ornaments/medallions/ghadames-medallion.svg', 'assets/v1/ornaments/medallions/ghadames-medallion.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('lavender-silver-frame', 'إطار لافندر وفضة', AssetType::Frame, 'frames/botanical/lavender-silver-frame.svg', 'assets/v1/frames/botanical/lavender-silver-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('lavender-corner', 'باقة لافندر', AssetType::Decoration, 'ornaments/corners/lavender-corner.svg', 'assets/v1/ornaments/corners/lavender-corner.png', 480, 480, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('lavender-divider', 'فاصل لافندر فضي', AssetType::Decoration, 'ornaments/dividers/lavender-divider.svg', 'assets/v1/ornaments/dividers/lavender-divider.png', 720, 120, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->visualAsset('lavender-wreath', 'إكليل اللافندر', AssetType::Icon, 'ornaments/medallions/lavender-wreath.svg', 'assets/v1/ornaments/medallions/lavender-wreath.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-template-packages'),
            $this->rasterAsset('ivory-arch-background', 'قوس عاجي معماري', AssetType::Background, 'backgrounds/ivory/ivory-arch-background.png', 'backgrounds/ivory/ivory-arch-background.png', 1080, 720, $rasterCapabilities, $ivoryLicense),
            $this->rasterAsset('ivory-botanical-ornament', 'زخرفة عاجية نباتية', AssetType::Decoration, 'ornaments/ivory/ivory-botanical-ornament.png', 'ornaments/ivory/ivory-botanical-ornament.png', 1080, 720, $rasterCapabilities, $ivoryLicense),
            $this->rasterAsset('ivory-swans', 'بجعتان عاجيتان', AssetType::Decoration, 'ornaments/ivory/ivory-swans.png', 'ornaments/ivory/ivory-swans.png', 1080, 720, $rasterCapabilities, $ivoryLicense),
            $this->visualAsset('ivory-chandelier-icon', 'أيقونة ثريّا', AssetType::Icon, 'icons/ivory/ivory-chandelier-icon.svg', 'assets/v1/icons/ivory/ivory-chandelier-icon.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-ivory-tale'),
            $this->visualAsset('ivory-clock-icon', 'أيقونة ساعة', AssetType::Icon, 'icons/ivory/ivory-clock-icon.svg', 'assets/v1/icons/ivory/ivory-clock-icon.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-ivory-tale'),
            $this->visualAsset('ivory-envelope', 'ظرف عاجي', AssetType::Decoration, 'ornaments/ivory/ivory-envelope.svg', 'assets/v1/ornaments/ivory/ivory-envelope.png', 720, 506, $visualCapabilities, $visualLicense, 'methaq-ivory-tale'),
            $this->visualAsset('ivory-heart-frame', 'إطار قلب', AssetType::Decoration, 'ornaments/ivory/ivory-heart-frame.svg', 'assets/v1/ornaments/ivory/ivory-heart-frame.png', 520, 477, $visualCapabilities, $visualLicense, 'methaq-ivory-tale'),
            $this->visualAsset('opening-ribbon-frame', 'إطار قصّ الشريط', AssetType::Frame, 'frames/opening/opening-ribbon-frame.svg', 'assets/v1/frames/opening/opening-ribbon-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-occasion-packages'),
            $this->visualAsset('opening-scissors-icon', 'مقصّ الافتتاح', AssetType::Icon, 'icons/opening/opening-scissors-icon.svg', 'assets/v1/icons/opening/opening-scissors-icon.png', 520, 520, $visualCapabilities, $visualLicense, 'methaq-occasion-packages'),
            $this->visualAsset('newborn-moon-frame', 'إطار هلال ونجوم', AssetType::Frame, 'frames/newborn/newborn-moon-frame.svg', 'assets/v1/frames/newborn/newborn-moon-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-occasion-packages'),
            $this->visualAsset('newborn-footprints', 'أقدام صغيرة', AssetType::Decoration, 'ornaments/newborn/newborn-footprints.svg', 'assets/v1/ornaments/newborn/newborn-footprints.png', 600, 400, $visualCapabilities, $visualLicense, 'methaq-occasion-packages'),
            $this->visualAsset('opening-modern-frame', 'إطار افتتاح عصري', AssetType::Frame, 'frames/opening/opening-modern-frame.svg', 'assets/v1/frames/opening/opening-modern-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-occasion-packages'),
            $this->visualAsset('newborn-blush-frame', 'إطار بالونات', AssetType::Frame, 'frames/newborn/newborn-blush-frame.svg', 'assets/v1/frames/newborn/newborn-blush-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-occasion-packages'),
            $this->visualAsset('graduation-navy-frame', 'إطار تخرج كحلي', AssetType::Frame, 'frames/graduation/graduation-navy-frame.svg', 'assets/v1/frames/graduation/graduation-navy-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-occasion-packages'),
            $this->visualAsset('noir-paper-frame', 'إطار ورقة ليلة عنابية', AssetType::Frame, 'frames/paper/noir-paper-frame.svg', 'assets/v1/frames/paper/noir-paper-frame.png', 1080, 1920, $visualCapabilities, $visualLicense, 'methaq-henna-noir'),
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
                'thumbnail_path' => 'editor/previews/templates/powder-gold.png',
                'preset' => TemplatePreset::PowderGold,
                'assets' => ['powder-botanical-frame', 'gold-divider', 'font-amiri', 'font-pinyon', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::BurgundyNoir->value,
                'name' => 'عنابي ملكي',
                'category' => EventCategory::Henna,
                'thumbnail_path' => 'editor/previews/templates/burgundy-noir.png',
                'preset' => TemplatePreset::BurgundyNoir,
                'assets' => ['burgundy-arch-frame', 'gold-divider', 'font-amiri', 'font-pinyon', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::HeritageVertical->value,
                'name' => 'ميثاق تراثي عمودي',
                'category' => EventCategory::MarriageContract,
                'thumbnail_path' => 'editor/previews/templates/heritage-vertical.png',
                'preset' => TemplatePreset::HeritageVertical,
                'assets' => [
                    'heritage-geometric-frame',
                    'gold-divider',
                    'wax-seal',
                    'methaq-pointed-arch-frame',
                    'methaq-arabesque-corner',
                    'methaq-eight-star-medallion',
                    'methaq-diamond-divider',
                    'methaq-geometric-side-border',
                    'font-amiri',
                    'font-noto-arabic',
                ],
            ],
            [
                'slug' => TemplatePreset::GraduationEmerald->value,
                'name' => 'تخرج زمردي',
                'category' => EventCategory::Graduation,
                'thumbnail_path' => 'editor/previews/templates/graduation-emerald.png',
                'preset' => TemplatePreset::GraduationEmerald,
                'assets' => ['graduation-rays-frame', 'gold-divider', 'font-amiri', 'font-pinyon', 'font-noto-arabic', 'font-manrope'],
            ],
            [
                'slug' => TemplatePreset::HennaNoir->value,
                'name' => 'ليلة عنابية',
                'category' => EventCategory::Henna,
                'thumbnail_path' => 'editor/previews/templates/henna-noir.svg',
                'preset' => TemplatePreset::HennaNoir,
                'assets' => ['noir-paper-frame', 'font-amiri', 'font-aref-ruqaa', 'font-cormorant', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::JasmineWhite->value,
                'name' => 'ياسمين أبيض',
                'category' => EventCategory::Wedding,
                'thumbnail_path' => 'editor/previews/templates/jasmine-white.png',
                'preset' => TemplatePreset::JasmineWhite,
                'assets' => ['jasmine-white-frame', 'jasmine-corner', 'jasmine-divider', 'jasmine-wreath', 'font-amiri', 'font-aref-ruqaa', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::MediterraneanBlue->value,
                'name' => 'زرقة المتوسط',
                'category' => EventCategory::MarriageContract,
                'thumbnail_path' => 'editor/previews/templates/mediterranean-blue.png',
                'preset' => TemplatePreset::MediterraneanBlue,
                'assets' => ['mediterranean-arch-frame', 'mediterranean-tile-corner', 'mediterranean-wave-divider', 'mediterranean-star-medallion', 'font-amiri', 'font-aref-ruqaa', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::GhadamesTerracotta->value,
                'name' => 'تراب غدامس',
                'category' => EventCategory::Henna,
                'thumbnail_path' => 'editor/previews/templates/ghadames-terracotta.png',
                'preset' => TemplatePreset::GhadamesTerracotta,
                'assets' => ['ghadames-wall-frame', 'ghadames-corner', 'ghadames-divider', 'ghadames-medallion', 'font-amiri', 'font-aref-ruqaa', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::LavenderSilver->value,
                'name' => 'لافندر وفضة',
                'category' => EventCategory::Wedding,
                'thumbnail_path' => 'editor/previews/templates/lavender-silver.png',
                'preset' => TemplatePreset::LavenderSilver,
                'assets' => ['lavender-silver-frame', 'lavender-corner', 'lavender-divider', 'lavender-wreath', 'font-amiri', 'font-pinyon', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::IvoryTale->value,
                'name' => 'حكاية بالعاجي',
                'category' => EventCategory::Wedding,
                'thumbnail_path' => 'editor/previews/templates/ivory-tale.png',
                'preset' => TemplatePreset::IvoryTale,
                'assets' => ['ivory-arch-background', 'ivory-botanical-ornament', 'ivory-swans', 'ivory-chandelier-icon', 'ivory-clock-icon', 'ivory-envelope', 'ivory-heart-frame', 'font-amiri', 'font-pinyon', 'font-cormorant', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::ProjectOpening->value,
                'name' => 'قصّ الشريط',
                'category' => EventCategory::ProjectOpening,
                'thumbnail_path' => 'editor/previews/templates/project-opening.png',
                'preset' => TemplatePreset::ProjectOpening,
                'assets' => ['opening-ribbon-frame', 'opening-scissors-icon', 'methaq-diamond-divider', 'font-amiri', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::Newborn->value,
                'name' => 'هلال المولود',
                'category' => EventCategory::Newborn,
                'thumbnail_path' => 'editor/previews/templates/newborn.png',
                'preset' => TemplatePreset::Newborn,
                'assets' => ['newborn-moon-frame', 'newborn-footprints', 'font-amiri', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::OpeningModern->value,
                'name' => 'افتتاح عصري',
                'category' => EventCategory::ProjectOpening,
                'thumbnail_path' => 'editor/previews/templates/opening-modern.png',
                'preset' => TemplatePreset::OpeningModern,
                'assets' => ['opening-modern-frame', 'gold-divider', 'font-amiri', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::NewbornBlush->value,
                'name' => 'بالونات المولود',
                'category' => EventCategory::Newborn,
                'thumbnail_path' => 'editor/previews/templates/newborn-blush.png',
                'preset' => TemplatePreset::NewbornBlush,
                'assets' => ['newborn-blush-frame', 'newborn-footprints', 'font-amiri', 'font-noto-arabic'],
            ],
            [
                'slug' => TemplatePreset::GraduationNavy->value,
                'name' => 'تخرج كحلي',
                'category' => EventCategory::Graduation,
                'thumbnail_path' => 'editor/previews/templates/graduation-navy.png',
                'preset' => TemplatePreset::GraduationNavy,
                'assets' => ['graduation-navy-frame', 'gold-divider', 'font-amiri', 'font-noto-arabic'],
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
                'thumbnail_path' => 'editor/previews/collections/v1/botanical-set.png',
                'items' => [
                    ['asset' => 'botanical-corner-left', 'placement' => $this->placement(0, 0, 360, 525, 0.9)],
                    ['asset' => 'botanical-corner-right', 'placement' => $this->placement(720, 1395, 360, 525, 0.9)],
                    ['asset' => 'gold-divider', 'placement' => $this->placement(240, 1115, 600, 80, 1)],
                ],
            ],
            [
                'slug' => 'heritage-seal-set',
                'name' => 'إطار تراثي وختم',
                'thumbnail_path' => 'editor/previews/collections/v1/heritage-set.png',
                'items' => [
                    ['asset' => 'heritage-geometric-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'wax-seal', 'placement' => $this->placement(420, 1480, 240, 240, 1)],
                ],
            ],
            [
                'slug' => 'graduation-gold-set',
                'name' => 'قوس التخرج والفاصل',
                'thumbnail_path' => 'editor/previews/collections/v1/graduation-set.png',
                'items' => [
                    ['asset' => 'graduation-rays-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'gold-divider', 'placement' => $this->placement(240, 1305, 600, 80, 1)],
                ],
            ],
            [
                'slug' => 'methaq-islamic-ornament-set',
                'name' => 'زخارف ميثاق الإسلامية',
                'thumbnail_path' => 'editor/previews/collections/v1/methaq-islamic-set.svg',
                'items' => [
                    ['asset' => 'methaq-pointed-arch-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'methaq-eight-star-medallion', 'placement' => $this->placement(390, 210, 300, 300, 1)],
                    ['asset' => 'methaq-diamond-divider', 'placement' => $this->placement(180, 1210, 720, 120, 1)],
                    ['asset' => 'methaq-geometric-side-border', 'placement' => $this->placement(845, 410, 120, 800, 0.84)],
                ],
            ],
            [
                'slug' => 'jasmine-white-set',
                'name' => 'ياسمين أبيض: إطار وزوايا وإكليل',
                'thumbnail_path' => 'editor/previews/collections/v1/jasmine-white-set.png',
                'items' => [
                    ['asset' => 'jasmine-white-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'jasmine-wreath', 'placement' => $this->placement(430, 1640, 220, 220, 0.9)],
                    ['asset' => 'jasmine-divider', 'placement' => $this->placement(180, 990, 720, 120, 1)],
                ],
            ],
            [
                'slug' => 'mediterranean-blue-set',
                'name' => 'زرقة المتوسط: قوس وبلاط وموج',
                'thumbnail_path' => 'editor/previews/collections/v1/mediterranean-blue-set.png',
                'items' => [
                    ['asset' => 'mediterranean-arch-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'mediterranean-star-medallion', 'placement' => $this->placement(490, 1530, 100, 100, 1)],
                    ['asset' => 'mediterranean-wave-divider', 'placement' => $this->placement(180, 1000, 720, 120, 1)],
                ],
            ],
            [
                'slug' => 'ghadames-terracotta-set',
                'name' => 'تراب غدامس: جدار ومثلثات وميدالية',
                'thumbnail_path' => 'editor/previews/collections/v1/ghadames-terracotta-set.png',
                'items' => [
                    ['asset' => 'ghadames-wall-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'ghadames-corner', 'placement' => $this->placement(70, 230, 220, 220, 0.9)],
                    ['asset' => 'ghadames-divider', 'placement' => $this->placement(180, 1060, 720, 120, 1)],
                ],
            ],
            [
                'slug' => 'lavender-silver-set',
                'name' => 'لافندر وفضة: إطار وباقة وإكليل',
                'thumbnail_path' => 'editor/previews/collections/v1/lavender-silver-set.png',
                'items' => [
                    ['asset' => 'lavender-silver-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'lavender-wreath', 'placement' => $this->placement(400, 1360, 280, 280, 0.9)],
                    ['asset' => 'lavender-divider', 'placement' => $this->placement(180, 980, 720, 120, 1)],
                ],
            ],
            [
                'slug' => 'ivory-tale-set',
                'name' => 'حكاية بالعاجي: قوس وزخرفة وبجعتان',
                'thumbnail_path' => 'editor/previews/collections/v1/ivory-tale-set.png',
                'items' => [
                    ['asset' => 'ivory-arch-background', 'placement' => $this->placement(0, 0, 1080, 720, 1)],
                    ['asset' => 'ivory-botanical-ornament', 'placement' => $this->placement(0, 450, 1080, 720, 1)],
                    ['asset' => 'ivory-swans', 'placement' => $this->placement(318, 1590, 444, 306, 1)],
                    ['asset' => 'ivory-chandelier-icon', 'placement' => $this->placement(220, 1160, 100, 118, 1)],
                    ['asset' => 'ivory-clock-icon', 'placement' => $this->placement(760, 1160, 100, 118, 1)],
                ],
            ],
            [
                'slug' => 'project-opening-set',
                'name' => 'قصّ الشريط: إطار ومقصّ وفاصل',
                'thumbnail_path' => 'editor/previews/collections/v1/project-opening-set.png',
                'items' => [
                    ['asset' => 'opening-ribbon-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'opening-scissors-icon', 'placement' => $this->placement(500, 1525, 80, 80, 1)],
                    ['asset' => 'methaq-diamond-divider', 'placement' => $this->placement(180, 900, 720, 120, 1)],
                ],
            ],
            [
                'slug' => 'newborn-moon-set',
                'name' => 'هلال المولود: إطار وأقدام صغيرة',
                'thumbnail_path' => 'editor/previews/collections/v1/newborn-moon-set.png',
                'items' => [
                    ['asset' => 'newborn-moon-frame', 'placement' => $this->placement(0, 0, 1080, 1920, 1)],
                    ['asset' => 'newborn-footprints', 'placement' => $this->placement(390, 1170, 300, 200, 1)],
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $capabilities @param array<string, mixed> $license */
    private function visualAsset(
        string $slug,
        string $name,
        AssetType $type,
        string $original,
        string $preview,
        int $width,
        int $height,
        array $capabilities,
        array $license,
        string $sourceProject = 'methaq-reference-directions',
    ): array {
        return [
            'slug' => $slug,
            'name' => $name,
            'type' => $type,
            'original_path' => 'editor/assets/v1/'.$original,
            'preview_path' => 'editor/previews/'.$preview,
            'mime_type' => 'image/svg+xml',
            'width' => $width,
            'height' => $height,
            'metadata' => $this->visualMetadata($original, $type, $sourceProject),
            'capabilities' => $capabilities,
            'license_metadata' => $license,
            'asset_version' => 1,
        ];
    }

    /** @param array<string, mixed> $capabilities @param array<string, mixed> $license */
    private function rasterAsset(
        string $slug,
        string $name,
        AssetType $type,
        string $original,
        string $preview,
        int $width,
        int $height,
        array $capabilities,
        array $license,
    ): array {
        return [
            'slug' => $slug,
            'name' => $name,
            'type' => $type,
            'original_path' => 'editor/assets/v1/'.$original,
            'preview_path' => 'editor/previews/assets/v1/'.$preview,
            'mime_type' => 'image/png',
            'width' => $width,
            'height' => $height,
            'metadata' => ['sourceProject' => 'methaq-ivory-tale', 'neutralExample' => true, 'family' => 'ivory', 'category' => 'ornament', 'placement' => 'canvas', 'orientation' => 'full', 'style' => 'embossed', 'colorMode' => 'fixed'],
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
            'preview_path' => 'editor/previews/assets/v1/fonts/font-sample.svg',
            'mime_type' => 'font/ttf',
            'width' => null,
            'height' => null,
            'metadata' => ['fontFamily' => $family, 'format' => 'truetype'],
            'capabilities' => ['languages' => str_contains($slug, 'pinyon') || str_contains($slug, 'manrope') || str_contains($slug, 'cormorant') ? ['en'] : ['ar', 'en']],
            'license_metadata' => ['license' => $license, 'redistribution' => true],
            'asset_version' => 1,
        ];
    }

    /** @return array<string, string|bool> */
    private function visualMetadata(string $original, AssetType $type, string $sourceProject): array
    {
        return [
            'sourceProject' => $sourceProject,
            'neutralExample' => true,
            'family' => $this->assetFamily($original),
            'category' => match (true) {
                str_starts_with($original, 'frames/') => 'frame',
                str_starts_with($original, 'seals/') => 'seal',
                default => 'ornament',
            },
            'placement' => match (true) {
                $type === AssetType::Frame => 'canvas',
                str_contains($original, '/corners/'), str_contains($original, '/botanical/') => 'corner',
                str_contains($original, '/dividers/') => 'divider',
                str_contains($original, '/medallions/') => 'center',
                str_contains($original, '/borders/') => 'side',
                str_starts_with($original, 'seals/') => 'seal',
                default => 'canvas',
            },
            'orientation' => match (true) {
                str_contains($original, '-left.') => 'left',
                str_contains($original, '-right.') => 'right',
                str_contains($original, '/corners/') => 'top-left',
                str_contains($original, '/dividers/') => 'horizontal',
                str_contains($original, '/borders/vertical/') => 'vertical',
                $type === AssetType::Frame => 'full',
                default => 'center',
            },
            'style' => match (true) {
                str_contains($original, 'botanical') => 'botanical',
                str_contains($original, 'arabesque') => 'arabesque',
                str_contains($original, 'graduation') => 'celebratory',
                str_contains($original, 'wax-seal') => 'classic',
                default => 'geometric',
            },
            'colorMode' => 'tintable',
        ];
    }

    private function assetFamily(string $original): string
    {
        return match (true) {
            str_contains($original, 'jasmine') => 'jasmine',
            str_contains($original, 'mediterranean') => 'mediterranean',
            str_contains($original, 'ghadames') => 'ghadames',
            str_contains($original, 'lavender') => 'lavender',
            str_contains($original, 'botanical') => 'botanical',
            str_contains($original, 'graduation') => 'graduation',
            str_contains($original, 'wax-seal'), str_contains($original, 'heritage') => 'heritage',
            str_contains($original, 'noir') => 'noir',
            default => 'islamic',
        };
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
