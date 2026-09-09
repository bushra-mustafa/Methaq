<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\DTOs\AssetReferenceData;
use App\Domains\Editor\DTOs\AudioData;
use App\Domains\Editor\DTOs\CanvasData;
use App\Domains\Editor\DTOs\ColorValueData;
use App\Domains\Editor\DTOs\DesignDocumentData;
use App\Domains\Editor\DTOs\EffectData;
use App\Domains\Editor\DTOs\EnvelopeData;
use App\Domains\Editor\DTOs\ImageLayerData;
use App\Domains\Editor\DTOs\LayerData;
use App\Domains\Editor\DTOs\LayerFrameData;
use App\Domains\Editor\DTOs\OpeningData;
use App\Domains\Editor\DTOs\PaletteData;
use App\Domains\Editor\DTOs\SceneConfigData;
use App\Domains\Editor\DTOs\TextLayerData;
use App\Domains\Editor\Enums\EffectId;
use App\Domains\Editor\Enums\EnvelopePresetId;
use App\Domains\Editor\Enums\ImageFit;
use App\Domains\Editor\Enums\Language;
use App\Domains\Editor\Enums\LayerType;
use App\Domains\Editor\Enums\MotionPolicy;
use App\Domains\Editor\Enums\OpeningType;
use App\Domains\Editor\Enums\PaletteRole;
use App\Domains\Editor\Enums\TemplatePreset;
use App\Domains\Editor\Enums\TextAlignment;
use App\Domains\Editor\Enums\TextDirection;
use App\Domains\Editor\Models\TemplateAsset;
use InvalidArgumentException;

final class TemplateDesignFactory
{
    /** @param array<string, TemplateAsset> $assets */
    public function make(TemplatePreset $preset, array $assets): DesignDocumentData
    {
        return match ($preset) {
            TemplatePreset::PowderGold => $this->powderGold($assets),
            TemplatePreset::BurgundyNoir => $this->burgundyNoir($assets),
            TemplatePreset::HeritageVertical => $this->heritageVertical($assets),
            TemplatePreset::GraduationEmerald => $this->graduationEmerald($assets),
        };
    }

    /** @param array<string, TemplateAsset> $assets */
    private function powderGold(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'powder-botanical-frame', 0, 0, 1080, 1920, true),
            $this->text('basmala', $assets, 'font-amiri', 'بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْم', Language::Arabic, TextDirection::RightToLeft, 140, 335, 800, 100, 44, 400, PaletteRole::SecondaryText),
            $this->text('names_en', $assets, 'font-pinyon', 'Sara & Omar', Language::English, TextDirection::LeftToRight, 90, 640, 900, 210, 150, 400, PaletteRole::PrimaryText),
            $this->text('invitation', $assets, 'font-amiri', 'يسرّنا دعوتكم لمشاركتنا ليلة الفرح', Language::Arabic, TextDirection::RightToLeft, 120, 910, 840, 120, 53, 400, PaletteRole::SecondaryText),
            $this->image('divider', $assets, 'gold-divider', 240, 1115, 600, 80, true),
            $this->text('details', $assets, 'font-noto-arabic', "السبت · السابعة مساءً\nقاعة ميثاق · طرابلس", Language::Arabic, TextDirection::RightToLeft, 140, 1255, 800, 190, 40, 500, PaletteRole::SecondaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#ead0d0', '#fffaf1', '#866143', '#6f5949', '#a78550', '#fff5da'),
            $this->envelopeScene('#d8b0b0', 'S & O', true, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function burgundyNoir(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('arch', $assets, 'burgundy-arch-frame', 0, 0, 1080, 1920, true),
            $this->text('occasion', $assets, 'font-amiri', 'دعوة حنّة', Language::Arabic, TextDirection::RightToLeft, 180, 385, 720, 110, 58, 400, PaletteRole::Accent),
            $this->text('name', $assets, 'font-pinyon', 'Lina', Language::English, TextDirection::LeftToRight, 140, 680, 800, 250, 190, 400, PaletteRole::PrimaryText),
            $this->text('message', $assets, 'font-amiri', 'بوجودكم تكتمل فرحتنا', Language::Arabic, TextDirection::RightToLeft, 140, 1010, 800, 120, 60, 400, PaletteRole::SecondaryText),
            $this->image('divider', $assets, 'gold-divider', 240, 1205, 600, 80, true),
            $this->text('details', $assets, 'font-noto-arabic', "الخميس · الثامنة مساءً\nدار ميثاق", Language::Arabic, TextDirection::RightToLeft, 170, 1335, 740, 180, 41, 500, PaletteRole::PrimaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#35121d', '#fff8ea', '#fff8ea', '#dec0c2', '#d8b982', '#f2b3c1'),
            $this->envelopeScene('#71334a', 'L', true, true),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function heritageVertical(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('heritage_frame', $assets, 'heritage-geometric-frame', 0, 0, 1080, 1920, true),
            $this->text('opening', $assets, 'font-amiri', 'بسم الله وعلى بركة الله', Language::Arabic, TextDirection::RightToLeft, 140, 360, 800, 100, 46, 400, PaletteRole::PrimaryText),
            $this->text('occasion', $assets, 'font-amiri', 'عقد قِران', Language::Arabic, TextDirection::RightToLeft, 160, 635, 760, 150, 88, 400, PaletteRole::PrimaryText),
            $this->text('names', $assets, 'font-amiri', 'يوسف وسلمى', Language::Arabic, TextDirection::RightToLeft, 110, 905, 860, 160, 83, 400, PaletteRole::Accent),
            $this->image('divider', $assets, 'gold-divider', 240, 1150, 600, 80, true),
            $this->text('details', $assets, 'font-noto-arabic', "بحضور الأهل والأحباب\nيوم الجمعة بعد صلاة العصر", Language::Arabic, TextDirection::RightToLeft, 130, 1285, 820, 180, 43, 500, PaletteRole::SecondaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#f5f2e3', '#ffffff', '#074b36', '#315e50', '#b69a50', '#d8c680'),
            $this->fadeScene(),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function graduationEmerald(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('graduation_frame', $assets, 'graduation-rays-frame', 0, 0, 1080, 1920, true),
            $this->text('class_year', $assets, 'font-pinyon', 'Class of 2027', Language::English, TextDirection::LeftToRight, 150, 555, 780, 150, 118, 400, PaletteRole::Accent),
            $this->text('occasion', $assets, 'font-amiri', 'حفل التخرج', Language::Arabic, TextDirection::RightToLeft, 160, 850, 760, 150, 88, 400, PaletteRole::PrimaryText),
            $this->text('graduate', $assets, 'font-amiri', 'ليان أحمد', Language::Arabic, TextDirection::RightToLeft, 150, 1100, 780, 150, 79, 400, PaletteRole::PrimaryText),
            $this->image('divider', $assets, 'gold-divider', 240, 1305, 600, 80, true),
            $this->text('message', $assets, 'font-noto-arabic', 'نحتفل معاً ببداية الطريق', Language::Arabic, TextDirection::RightToLeft, 150, 1435, 780, 120, 42, 500, PaletteRole::SecondaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#032e23', '#074b36', '#f5f2e3', '#d9e5df', '#d7c07d', '#fff1bd'),
            new SceneConfigData(
                1,
                new OpeningData(OpeningType::Fade, 1100),
                [new EffectData(EffectId::Sparkle, 1, true, ColorValueData::palette(PaletteRole::Effect), 0.4, 0.85)],
                new AudioData(false, null, 0.6),
                MotionPolicy::System,
            ),
        );
    }

    /** @param list<LayerData> $layers */
    private function document(array $layers, PaletteData $palette, SceneConfigData $scene): DesignDocumentData
    {
        return new DesignDocumentData(
            DesignSchema::VERSION,
            new CanvasData(1, 1080, 1920, ColorValueData::palette(PaletteRole::Background), $layers),
            $palette,
            $scene,
        );
    }

    private function envelopeScene(string $paperColor, string $monogram, bool $sparkle, bool $smoke): SceneConfigData
    {
        return new SceneConfigData(
            1,
            new OpeningData(
                OpeningType::Envelope,
                1700,
                new EnvelopeData(
                    EnvelopePresetId::ClassicFold,
                    1,
                    ColorValueData::literal($paperColor),
                    ColorValueData::palette(PaletteRole::Surface),
                    ColorValueData::palette(PaletteRole::Accent),
                    $monogram,
                ),
            ),
            [
                new EffectData(EffectId::Sparkle, 1, $sparkle, ColorValueData::palette(PaletteRole::Effect), 0.52, 0.9),
                new EffectData(EffectId::Smoke, 1, $smoke, ColorValueData::palette(PaletteRole::Effect), 0.28, 0.65),
            ],
            new AudioData(false, null, 0.6),
            MotionPolicy::System,
        );
    }

    private function fadeScene(): SceneConfigData
    {
        return new SceneConfigData(
            1,
            new OpeningData(OpeningType::Fade, 900),
            [],
            new AudioData(false, null, 0.6),
            MotionPolicy::System,
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function image(string $id, array $assets, string $assetSlug, float $x, float $y, float $width, float $height, bool $locked): LayerData
    {
        return new LayerData(
            $id,
            LayerType::Image,
            new LayerFrameData($x, $y, $width, $height, 1, 1, 0, 1),
            $locked,
            true,
            new ImageLayerData($this->reference($assets, $assetSlug), ImageFit::Contain),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function text(
        string $id,
        array $assets,
        string $fontSlug,
        string $content,
        Language $language,
        TextDirection $direction,
        float $x,
        float $y,
        float $width,
        float $height,
        float $fontSize,
        int $fontWeight,
        PaletteRole $fill,
    ): LayerData {
        return new LayerData(
            $id,
            LayerType::Text,
            new LayerFrameData($x, $y, $width, $height, 1, 1, 0, 1),
            false,
            true,
            new TextLayerData(
                $content,
                $language,
                $direction,
                TextAlignment::Center,
                $this->reference($assets, $fontSlug),
                $fontSize,
                $fontWeight,
                ColorValueData::palette($fill),
            ),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function reference(array $assets, string $slug): AssetReferenceData
    {
        $asset = $assets[$slug] ?? null;
        if (! $asset instanceof TemplateAsset || $asset->getKey() === null) {
            throw new InvalidArgumentException("Template asset {$slug} is unavailable.");
        }

        return new AssetReferenceData((string) $asset->getKey(), $asset->asset_version);
    }
}
