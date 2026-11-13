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
use App\Domains\Editor\DTOs\InvitationCoverData;
use App\Domains\Editor\DTOs\LayerData;
use App\Domains\Editor\DTOs\LayerFrameData;
use App\Domains\Editor\DTOs\OpeningData;
use App\Domains\Editor\DTOs\PaletteData;
use App\Domains\Editor\DTOs\SceneBackdropData;
use App\Domains\Editor\DTOs\SceneConfigData;
use App\Domains\Editor\DTOs\ShapeLayerData;
use App\Domains\Editor\DTOs\TextLayerData;
use App\Domains\Editor\Enums\EffectId;
use App\Domains\Editor\Enums\EnvelopePresetId;
use App\Domains\Editor\Enums\ImageFit;
use App\Domains\Editor\Enums\Language;
use App\Domains\Editor\Enums\LayerType;
use App\Domains\Editor\Enums\MotionPolicy;
use App\Domains\Editor\Enums\OpeningType;
use App\Domains\Editor\Enums\PaletteRole;
use App\Domains\Editor\Enums\ShapeKind;
use App\Domains\Editor\Enums\TemplatePreset;
use App\Domains\Editor\Enums\TextAlignment;
use App\Domains\Editor\Enums\TextDirection;
use App\Domains\Editor\Models\TemplateAsset;
use InvalidArgumentException;

final class TemplateDesignFactory
{
    /** Library audio track referenced by the henna-noir template. */
    public const HENNA_AUDIO_SLUG = 'audio-henna-celebration';

    /** @param array<string, TemplateAsset> $assets */
    public function make(TemplatePreset $preset, array $assets): DesignDocumentData
    {
        return match ($preset) {
            TemplatePreset::PowderGold => $this->powderGold($assets),
            TemplatePreset::BurgundyNoir => $this->burgundyNoir($assets),
            TemplatePreset::HeritageVertical => $this->heritageVertical($assets),
            TemplatePreset::GraduationEmerald => $this->graduationEmerald($assets),
            TemplatePreset::HennaNoir => $this->hennaNoir($assets),
            TemplatePreset::JasmineWhite => $this->jasmineWhite($assets),
            TemplatePreset::MediterraneanBlue => $this->mediterraneanBlue($assets),
            TemplatePreset::GhadamesTerracotta => $this->ghadamesTerracotta($assets),
            TemplatePreset::LavenderSilver => $this->lavenderSilver($assets),
            TemplatePreset::IvoryTale => $this->ivoryTale($assets),
            TemplatePreset::ProjectOpening => $this->projectOpening($assets),
            TemplatePreset::Newborn => $this->newborn($assets),
            TemplatePreset::OpeningModern => $this->openingModern($assets),
            TemplatePreset::NewbornBlush => $this->newbornBlush($assets),
            TemplatePreset::GraduationNavy => $this->graduationNavy($assets),
            TemplatePreset::QiranSand => $this->qiranSand($assets),
        };
    }

    /** @param array<string, TemplateAsset> $assets */
    private function powderGold(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'powder-botanical-frame', 0, 0, 1080, 1920, true),
            $this->text('basmala', $assets, 'font-amiri', 'بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْم', Language::Arabic, TextDirection::RightToLeft, 140, 335, 800, 100, 44, 400, PaletteRole::SecondaryText),
            $this->text('names_en', $assets, 'font-pinyon', 'Asma & Mohamed', Language::English, TextDirection::LeftToRight, 90, 650, 900, 190, 128, 400, PaletteRole::PrimaryText),
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
            $this->text('name', $assets, 'font-pinyon', 'Nawara', Language::English, TextDirection::LeftToRight, 140, 680, 800, 250, 190, 400, PaletteRole::PrimaryText),
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
            $this->text('names', $assets, 'font-amiri', 'سالم وهنية', Language::Arabic, TextDirection::RightToLeft, 110, 905, 860, 160, 83, 400, PaletteRole::Accent),
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
            $this->text('graduate', $assets, 'font-amiri', 'ريم سالم', Language::Arabic, TextDirection::RightToLeft, 150, 1100, 780, 150, 79, 400, PaletteRole::PrimaryText),
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

    /**
     * «ليلة عنابية»: an editable paper card shown inside the henna-noir scene
     * (animated smoke backdrop, tilted envelope and henna music).
     *
     * @param  array<string, TemplateAsset>  $assets
     */
    private function hennaNoir(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('paper_frame', $assets, 'noir-paper-frame', 0, 0, 1080, 1920, true),
            $this->text('kicker', $assets, 'font-amiri', 'لَيْلَةٌ مِنَ الفَرَح', Language::Arabic, TextDirection::RightToLeft, 190, 225, 700, 80, 40, 400, PaletteRole::Accent),
            $this->text('message', $assets, 'font-amiri', "يسعدنا أن نجتمع بكم في ليلة\nنحتفل فيها بالحب والفرح،\nونرجو أن تكون أيامنا مليئة بالبركة.", Language::Arabic, TextDirection::RightToLeft, 120, 320, 840, 220, 35, 400, PaletteRole::SecondaryText),
            $this->text('blessing', $assets, 'font-amiri', "اللهم بارك لنا وبارك علينا\nواجمع بيننا في خير", Language::Arabic, TextDirection::RightToLeft, 120, 585, 840, 220, 56, 400, PaletteRole::PrimaryText),
            $this->text('intro', $assets, 'font-noto-arabic', 'يسعدنا دعوتكم لمشاركتنا ليلة الحنّة', Language::Arabic, TextDirection::RightToLeft, 150, 845, 780, 80, 32, 400, PaletteRole::SecondaryText),
            $this->text('names', $assets, 'font-aref-ruqaa', 'نوارة', Language::Arabic, TextDirection::RightToLeft, 120, 945, 840, 230, 150, 400, PaletteRole::PrimaryText),
            $this->text('date_label', $assets, 'font-noto-arabic', 'الموعد', Language::Arabic, TextDirection::RightToLeft, 340, 1270, 400, 60, 28, 400, PaletteRole::SecondaryText),
            $this->text('date', $assets, 'font-cormorant', '18 / 9', Language::English, TextDirection::LeftToRight, 240, 1330, 600, 140, 104, 400, PaletteRole::PrimaryText),
            $this->text('date_note', $assets, 'font-noto-arabic', 'الخميس، الثامنة مساءً', Language::Arabic, TextDirection::RightToLeft, 240, 1475, 600, 60, 28, 400, PaletteRole::SecondaryText),
            $this->text('closing', $assets, 'font-amiri', 'بحضوركم تكتمل فرحتنا', Language::Arabic, TextDirection::RightToLeft, 140, 1630, 800, 110, 56, 400, PaletteRole::PrimaryText),
        ];

        $palette = new PaletteData(1, '#fff8ea', '#fffdf7', '#5b1830', '#7f5a4a', '#b48a4a', '#f2b3c1');
        $scene = new SceneConfigData(
            1,
            new OpeningData(
                OpeningType::Envelope,
                1700,
                new EnvelopeData(
                    EnvelopePresetId::ClassicFold,
                    1,
                    ColorValueData::literal('#71334a'),
                    ColorValueData::palette(PaletteRole::Background),
                    ColorValueData::palette(PaletteRole::Accent),
                    'ن',
                ),
            ),
            [new EffectData(EffectId::Sparkle, 1, true, ColorValueData::palette(PaletteRole::Effect), 0.5, 0.9)],
            new AudioData(true, $this->reference($assets, self::HENNA_AUDIO_SLUG), 0.7),
            MotionPolicy::System,
            new InvitationCoverData('فرحتنا تحلى بوجودكم', 'نوارة', '18 / 9', 'اللهم بارك لنا وبارك علينا واجمع بيننا في خير', 'ar', 'halo', false),
            new SceneBackdropData('henna-noir'),
        );

        return $this->document($layers, $palette, $scene);
    }

    /** «ياسمين أبيض». @param array<string, TemplateAsset> $assets */
    private function jasmineWhite(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'jasmine-white-frame', 0, 0, 1080, 1920, true),
            $this->text('basmala', $assets, 'font-amiri', 'بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْم', Language::Arabic, TextDirection::RightToLeft, 160, 300, 760, 90, 42, 400, PaletteRole::SecondaryText),
            $this->text('names', $assets, 'font-aref-ruqaa', 'يوسف وملاك', Language::Arabic, TextDirection::RightToLeft, 110, 520, 860, 230, 128, 400, PaletteRole::PrimaryText),
            $this->text('invitation', $assets, 'font-amiri', 'يتشرفان بدعوتكم لحضور حفل زفافهما', Language::Arabic, TextDirection::RightToLeft, 120, 820, 840, 110, 50, 400, PaletteRole::SecondaryText),
            $this->image('divider', $assets, 'jasmine-divider', 180, 990, 720, 120, true),
            $this->text('details', $assets, 'font-noto-arabic', "الخميس 14/8/2027 · الثامنة مساءً\nقاعة الياسمين · طرابلس", Language::Arabic, TextDirection::RightToLeft, 150, 1170, 780, 190, 40, 500, PaletteRole::SecondaryText),
            $this->text('closing', $assets, 'font-amiri', 'بحضوركم تكتمل فرحتنا', Language::Arabic, TextDirection::RightToLeft, 160, 1470, 760, 110, 48, 400, PaletteRole::PrimaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#fbfaf5', '#ffffff', '#3f5a4a', '#6f7f72', '#8fae96', '#e9f0e6'),
            $this->envelopeScene('#dfe8dc', 'ي م', true, false),
        );
    }

    /** «زرقة المتوسط». @param array<string, TemplateAsset> $assets */
    private function mediterraneanBlue(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('arch', $assets, 'mediterranean-arch-frame', 0, 0, 1080, 1920, true),
            $this->text('opening', $assets, 'font-amiri', 'بسم الله وعلى بركة الله', Language::Arabic, TextDirection::RightToLeft, 240, 330, 600, 90, 44, 400, PaletteRole::SecondaryText),
            $this->text('occasion', $assets, 'font-amiri', 'عقد قِران', Language::Arabic, TextDirection::RightToLeft, 200, 520, 680, 150, 92, 400, PaletteRole::PrimaryText),
            $this->text('names', $assets, 'font-aref-ruqaa', 'عبدالسلام وخديجة', Language::Arabic, TextDirection::RightToLeft, 170, 760, 740, 170, 88, 400, PaletteRole::PrimaryText),
            $this->image('divider', $assets, 'mediterranean-wave-divider', 180, 1000, 720, 120, true),
            $this->text('details', $assets, 'font-noto-arabic', "الجمعة 5/9/2027 · بعد صلاة العصر\nبيت العائلة · المدينة القديمة، طرابلس", Language::Arabic, TextDirection::RightToLeft, 190, 1170, 700, 190, 38, 500, PaletteRole::SecondaryText),
            $this->text('verse', $assets, 'font-amiri', 'وجعل بينكم مودّة ورحمة', Language::Arabic, TextDirection::RightToLeft, 200, 1440, 680, 100, 46, 400, PaletteRole::PrimaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#f4efe4', '#fffaf0', '#1d3557', '#4a6380', '#c8a165', '#a8c5da'),
            $this->envelopeScene('#1d3557', 'ع خ', true, false),
        );
    }

    /** «تراب غدامس». @param array<string, TemplateAsset> $assets */
    private function ghadamesTerracotta(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('wall', $assets, 'ghadames-wall-frame', 0, 0, 1080, 1920, true),
            $this->text('occasion', $assets, 'font-amiri', 'ليلة الحنّة', Language::Arabic, TextDirection::RightToLeft, 240, 450, 600, 110, 64, 400, PaletteRole::Accent),
            $this->text('name', $assets, 'font-aref-ruqaa', 'سعاد', Language::Arabic, TextDirection::RightToLeft, 140, 600, 800, 240, 160, 400, PaletteRole::PrimaryText),
            $this->text('message', $assets, 'font-amiri', 'يسعدنا حضوركم لمشاركتنا فرحتنا', Language::Arabic, TextDirection::RightToLeft, 130, 900, 820, 110, 50, 400, PaletteRole::SecondaryText),
            $this->image('divider', $assets, 'ghadames-divider', 180, 1060, 720, 120, true),
            $this->text('details', $assets, 'font-noto-arabic', "الخميس 21/10/2027 · الثامنة مساءً\nبيت العائلة · غدامس", Language::Arabic, TextDirection::RightToLeft, 150, 1230, 780, 190, 40, 500, PaletteRole::SecondaryText),
            $this->text('closing', $assets, 'font-amiri', 'بحضوركم تحلى الليلة', Language::Arabic, TextDirection::RightToLeft, 160, 1490, 760, 110, 48, 400, PaletteRole::PrimaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#f6ead7', '#fff6e8', '#7a2e1c', '#8a5440', '#b4442a', '#e8b48a'),
            $this->envelopeScene('#b4442a', 'س', true, false),
        );
    }

    /** «لافندر وفضة». @param array<string, TemplateAsset> $assets */
    private function lavenderSilver(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'lavender-silver-frame', 0, 0, 1080, 1920, true),
            $this->text('kicker', $assets, 'font-amiri', 'بكلّ الحب', Language::Arabic, TextDirection::RightToLeft, 290, 270, 500, 90, 44, 400, PaletteRole::SecondaryText),
            $this->text('names_en', $assets, 'font-pinyon', 'Malak & Ali', Language::English, TextDirection::LeftToRight, 110, 430, 860, 230, 150, 400, PaletteRole::PrimaryText),
            $this->text('names_ar', $assets, 'font-amiri', 'ملاك وعلي', Language::Arabic, TextDirection::RightToLeft, 240, 680, 600, 110, 60, 400, PaletteRole::Accent),
            $this->text('invitation', $assets, 'font-amiri', 'يتشرفان بدعوتكم لحضور حفل زفافهما', Language::Arabic, TextDirection::RightToLeft, 120, 830, 840, 110, 48, 400, PaletteRole::SecondaryText),
            $this->image('divider', $assets, 'lavender-divider', 180, 980, 720, 120, true),
            $this->text('details', $assets, 'font-noto-arabic', "السبت 2/10/2027 · السابعة مساءً\nقاعة اللافندر · بنغازي", Language::Arabic, TextDirection::RightToLeft, 180, 1140, 720, 190, 40, 500, PaletteRole::SecondaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#f5f2f8', '#fdfcff', '#4f3f6e', '#7d7393', '#9b87c4', '#d9cdea'),
            $this->envelopeScene('#cbbfe3', 'م ع', true, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function ivoryTale(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('arch', $assets, 'ivory-arch-background', 0, 0, 1080, 720, true),
            $this->shape('paper', 0, 450, 1080, 1470, PaletteRole::Surface),
            $this->image('ornament', $assets, 'ivory-botanical-ornament', 0, 450, 1080, 720, true),
            $this->text('title', $assets, 'font-pinyon', 'Wedding Invitation', Language::English, TextDirection::LeftToRight, 140, 650, 800, 110, 80, 400, PaletteRole::PrimaryText),
            $this->text('names', $assets, 'font-amiri', 'ليان وآدم', Language::Arabic, TextDirection::RightToLeft, 170, 765, 740, 130, 82, 400, PaletteRole::PrimaryText),
            $this->text('message', $assets, 'font-amiri', "إلى من لهم في قلوبنا مكانٌ عزيز\nبكل الحب ندعوكم لمشاركتنا فرحة زفافنا", Language::Arabic, TextDirection::RightToLeft, 120, 910, 840, 130, 44, 400, PaletteRole::PrimaryText),
            $this->text('message_en', $assets, 'font-cormorant', "With joyful hearts, we invite you to celebrate\nour wedding and the beginning of our forever.", Language::English, TextDirection::LeftToRight, 120, 1050, 840, 80, 27, 400, PaletteRole::SecondaryText),
            $this->image('venue_icon', $assets, 'ivory-chandelier-icon', 220, 1160, 100, 118, false),
            $this->text('venue_name', $assets, 'font-amiri', 'قاعة الياسمين', Language::Arabic, TextDirection::RightToLeft, 60, 1292, 420, 56, 36, 400, PaletteRole::PrimaryText),
            $this->text('venue_address', $assets, 'font-noto-arabic', 'طرابلس · طريق الشط', Language::Arabic, TextDirection::RightToLeft, 60, 1352, 420, 50, 26, 400, PaletteRole::SecondaryText),
            $this->image('schedule_icon', $assets, 'ivory-clock-icon', 760, 1160, 100, 118, false),
            $this->text('date', $assets, 'font-cormorant', '19 / 06 / 2027', Language::English, TextDirection::LeftToRight, 600, 1292, 420, 56, 32, 500, PaletteRole::PrimaryText),
            $this->text('day', $assets, 'font-noto-arabic', 'يوم السبت', Language::Arabic, TextDirection::RightToLeft, 600, 1352, 420, 50, 27, 400, PaletteRole::SecondaryText),
            $this->text('time', $assets, 'font-amiri', 'السابعة والنصف مساءً', Language::Arabic, TextDirection::RightToLeft, 600, 1404, 420, 56, 31, 400, PaletteRole::PrimaryText),
            $this->text('closing', $assets, 'font-amiri', 'بحضوركم تكتمل فرحتنا', Language::Arabic, TextDirection::RightToLeft, 170, 1500, 740, 70, 46, 400, PaletteRole::PrimaryText),
            $this->image('swans', $assets, 'ivory-swans', 318, 1590, 444, 306, false),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#f8f5ee', '#eee8dd', '#49443e', '#6d675e', '#9a8466', '#d8cfc0'),
            $this->envelopeScene('#e4dac8', 'ل آ', false, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function projectOpening(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'opening-ribbon-frame', 0, 0, 1080, 1920, true),
            $this->text('kicker', $assets, 'font-amiri', 'يتشرّف بدعوتكم', Language::Arabic, TextDirection::RightToLeft, 240, 300, 600, 90, 52, 400, PaletteRole::Accent),
            $this->text('title', $assets, 'font-amiri', 'حفل افتتاح', Language::Arabic, TextDirection::RightToLeft, 140, 450, 800, 190, 130, 400, PaletteRole::PrimaryText),
            $this->text('name', $assets, 'font-amiri', 'اسم مشروعك', Language::Arabic, TextDirection::RightToLeft, 120, 680, 840, 150, 96, 400, PaletteRole::Accent),
            $this->image('divider', $assets, 'methaq-diamond-divider', 180, 900, 720, 120, true),
            $this->text('message', $assets, 'font-amiri', "ندعوكم لمشاركتنا لحظة قصّ الشريط\nوبداية رحلة جديدة", Language::Arabic, TextDirection::RightToLeft, 120, 1060, 840, 150, 48, 400, PaletteRole::SecondaryText),
            $this->text('details', $assets, 'font-noto-arabic', "السبت · السابعة مساءً\nطرابلس · شارع الجمهورية", Language::Arabic, TextDirection::RightToLeft, 140, 1270, 800, 170, 40, 500, PaletteRole::PrimaryText),
            $this->text('closing', $assets, 'font-amiri', 'بحضوركم يكتمل نجاحنا', Language::Arabic, TextDirection::RightToLeft, 190, 1465, 700, 80, 46, 400, PaletteRole::Accent),
            $this->image('scissors', $assets, 'opening-scissors-icon', 500, 1525, 80, 80, false),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#12303a', '#1b4452', '#f6efe0', '#cfd8d6', '#e2c59d', '#e2c59d'),
            $this->envelopeScene('#1f4a58', 'م', true, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function newborn(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'newborn-moon-frame', 0, 0, 1080, 1920, true),
            $this->text('kicker', $assets, 'font-amiri', 'بكل الحب والفرح', Language::Arabic, TextDirection::RightToLeft, 240, 470, 600, 90, 50, 400, PaletteRole::Accent),
            $this->text('title', $assets, 'font-amiri', 'مرحباً بالمولود', Language::Arabic, TextDirection::RightToLeft, 140, 570, 800, 170, 112, 400, PaletteRole::PrimaryText),
            $this->text('name', $assets, 'font-amiri', 'ليلى', Language::Arabic, TextDirection::RightToLeft, 240, 770, 600, 160, 120, 400, PaletteRole::Accent),
            $this->text('message', $assets, 'font-amiri', "يسعدنا دعوتكم لمشاركتنا فرحة قدوم مولودنا\nوالدعاء له بالصحة والعافية", Language::Arabic, TextDirection::RightToLeft, 120, 980, 840, 150, 46, 400, PaletteRole::SecondaryText),
            $this->image('footprints', $assets, 'newborn-footprints', 390, 1170, 300, 200, false),
            $this->text('details', $assets, 'font-noto-arabic', "الجمعة · الخامسة مساءً\nمنزل العائلة · بنغازي", Language::Arabic, TextDirection::RightToLeft, 140, 1410, 800, 170, 40, 500, PaletteRole::PrimaryText),
            $this->text('closing', $assets, 'font-amiri', 'دمتم لنا فرحة', Language::Arabic, TextDirection::RightToLeft, 240, 1600, 600, 80, 48, 400, PaletteRole::Accent),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#fbf6ee', '#ffffff', '#3f5a5e', '#6d7f82', '#b8935a', '#cfe0e3'),
            $this->envelopeScene('#bcd6da', 'م', true, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function openingModern(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'opening-modern-frame', 0, 0, 1080, 1920, true),
            $this->text('kicker', $assets, 'font-amiri', 'ندعوكم لحضور', Language::Arabic, TextDirection::RightToLeft, 240, 360, 600, 80, 46, 400, PaletteRole::SecondaryText),
            $this->text('title', $assets, 'font-amiri', 'افتتاح', Language::Arabic, TextDirection::RightToLeft, 140, 470, 800, 230, 150, 400, PaletteRole::PrimaryText),
            $this->text('name', $assets, 'font-amiri', 'اسم مشروعك', Language::Arabic, TextDirection::RightToLeft, 120, 740, 840, 150, 92, 400, PaletteRole::Accent),
            $this->image('divider', $assets, 'gold-divider', 240, 930, 600, 80, true),
            $this->text('message', $assets, 'font-amiri', "بداية جديدة نحتفي بها معكم\nوبحضوركم يكتمل الافتتاح", Language::Arabic, TextDirection::RightToLeft, 120, 1060, 840, 150, 48, 400, PaletteRole::SecondaryText),
            $this->text('details', $assets, 'font-noto-arabic', "السبت · السابعة مساءً\nطرابلس · شارع الجمهورية", Language::Arabic, TextDirection::RightToLeft, 140, 1290, 800, 170, 40, 500, PaletteRole::PrimaryText),
            $this->text('closing', $assets, 'font-amiri', 'في انتظاركم', Language::Arabic, TextDirection::RightToLeft, 240, 1500, 600, 80, 46, 400, PaletteRole::Accent),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#f6f2e8', '#ffffff', '#203322', '#4b5a4c', '#7a613b', '#c9b48a'),
            $this->envelopeScene('#e4dcc8', 'م', false, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function newbornBlush(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'newborn-blush-frame', 0, 0, 1080, 1920, true),
            $this->text('kicker', $assets, 'font-amiri', 'وصلت الفرحة', Language::Arabic, TextDirection::RightToLeft, 240, 520, 600, 90, 50, 400, PaletteRole::Accent),
            $this->text('title', $assets, 'font-amiri', 'أهلاً بالمولود', Language::Arabic, TextDirection::RightToLeft, 140, 620, 800, 170, 108, 400, PaletteRole::PrimaryText),
            $this->text('name', $assets, 'font-amiri', 'محمد', Language::Arabic, TextDirection::RightToLeft, 240, 820, 600, 160, 120, 400, PaletteRole::Accent),
            $this->text('message', $assets, 'font-amiri', "ندعوكم لمشاركتنا فرحة استقباله\nوالدعاء له بطول العمر والصحة", Language::Arabic, TextDirection::RightToLeft, 120, 1030, 840, 150, 46, 400, PaletteRole::SecondaryText),
            $this->image('footprints', $assets, 'newborn-footprints', 390, 1210, 300, 200, false),
            $this->text('details', $assets, 'font-noto-arabic', "الخميس · السادسة مساءً\nمنزل العائلة · طرابلس", Language::Arabic, TextDirection::RightToLeft, 140, 1440, 800, 170, 40, 500, PaletteRole::PrimaryText),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#fdf2f1', '#ffffff', '#7a4a52', '#9a7078', '#b9806e', '#f2c9c0'),
            $this->envelopeScene('#f3cfc8', 'م', true, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function graduationNavy(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'graduation-navy-frame', 0, 0, 1080, 1920, true),
            $this->text('kicker', $assets, 'font-amiri', 'بكل فخر ندعوكم لحضور', Language::Arabic, TextDirection::RightToLeft, 190, 500, 700, 90, 48, 400, PaletteRole::SecondaryText),
            $this->text('title', $assets, 'font-amiri', 'حفل التخرج', Language::Arabic, TextDirection::RightToLeft, 140, 590, 800, 190, 124, 400, PaletteRole::PrimaryText),
            $this->text('name', $assets, 'font-amiri', 'اسم الخريج', Language::Arabic, TextDirection::RightToLeft, 140, 810, 800, 160, 108, 400, PaletteRole::Accent),
            $this->image('divider', $assets, 'gold-divider', 240, 1020, 600, 80, true),
            $this->text('message', $assets, 'font-amiri', "لنشارككم فرحة هذا الإنجاز\nوالدعاء بدوام التوفيق", Language::Arabic, TextDirection::RightToLeft, 120, 1160, 840, 150, 48, 400, PaletteRole::SecondaryText),
            $this->text('details', $assets, 'font-noto-arabic', "الأحد · السابعة مساءً\nقاعة الجامعة · طرابلس", Language::Arabic, TextDirection::RightToLeft, 140, 1380, 800, 170, 40, 500, PaletteRole::PrimaryText),
            $this->text('closing', $assets, 'font-amiri', 'مبارك للجميع', Language::Arabic, TextDirection::RightToLeft, 240, 1610, 600, 80, 50, 400, PaletteRole::Accent),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#101c3a', '#1c2b52', '#f4ecd8', '#c9cfdc', '#d8b66a', '#e9d29b'),
            $this->envelopeScene('#1c2b52', 'م', true, false),
        );
    }

    /** @param array<string, TemplateAsset> $assets */
    private function qiranSand(array $assets): DesignDocumentData
    {
        $layers = [
            $this->image('frame', $assets, 'qiran-sand-frame', 0, 0, 1080, 1920, true),
            $this->text('family', $assets, 'font-amiri', "أفراح\nآل العائلة", Language::Arabic, TextDirection::RightToLeft, 432, 36, 216, 150, 48, 400, PaletteRole::PrimaryText),
            $this->text('basmala', $assets, 'font-aref-ruqaa', 'بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْم', Language::Arabic, TextDirection::RightToLeft, 140, 290, 800, 130, 74, 400, PaletteRole::PrimaryText),
            $this->text('verse', $assets, 'font-amiri', 'قال تعالى: ﴿ وَمِنْ آيَاتِهِ أَنْ خَلَقَ لَكُم مِّنْ أَنفُسِكُمْ أَزْوَاجًا لِّتَسْكُنُوا إِلَيْهَا وَجَعَلَ بَيْنَكُم مَّوَدَّةً وَرَحْمَةً ۚ إِنَّ فِي ذَٰلِكَ لَآيَاتٍ لِّقَوْمٍ يَتَفَكَّرُونَ ﴾', Language::Arabic, TextDirection::RightToLeft, 220, 450, 640, 190, 36, 400, PaletteRole::SecondaryText),
            $this->text('host_intro', $assets, 'font-amiri', 'يتشرف السيد /', Language::Arabic, TextDirection::RightToLeft, 240, 680, 600, 60, 38, 400, PaletteRole::SecondaryText),
            $this->text('host', $assets, 'font-amiri', 'اسم والد العريس', Language::Arabic, TextDirection::RightToLeft, 140, 735, 800, 90, 64, 400, PaletteRole::PrimaryText),
            $this->text('invite', $assets, 'font-amiri', 'بدعوتكم لحضور عقد قِران ابنه', Language::Arabic, TextDirection::RightToLeft, 140, 830, 800, 70, 40, 400, PaletteRole::SecondaryText),
            $this->text('title', $assets, 'font-amiri', 'المهندس', Language::Arabic, TextDirection::RightToLeft, 240, 925, 600, 90, 60, 400, PaletteRole::PrimaryText),
            $this->text('groom', $assets, 'font-aref-ruqaa', 'اسم العريس', Language::Arabic, TextDirection::RightToLeft, 90, 1005, 900, 250, 170, 400, PaletteRole::PrimaryText),
            $this->text('groom_family', $assets, 'font-amiri', 'اسم العائلة', Language::Arabic, TextDirection::RightToLeft, 240, 1265, 600, 100, 70, 400, PaletteRole::PrimaryText),
            $this->text('blessing', $assets, 'font-amiri', 'وذلك بمشيئة الله تعالى', Language::Arabic, TextDirection::RightToLeft, 240, 1385, 600, 60, 38, 400, PaletteRole::SecondaryText),
            $this->image('date_icon', $assets, 'qiran-calendar-icon', 782, 1480, 80, 80, false),
            $this->image('venue_icon', $assets, 'qiran-pin-icon', 500, 1480, 80, 80, false),
            $this->image('time_icon', $assets, 'qiran-clock-icon', 218, 1480, 80, 80, false),
            $this->text('date', $assets, 'font-noto-arabic', "يوم السبت\n31 / 10 / 2027", Language::Arabic, TextDirection::RightToLeft, 682, 1580, 280, 130, 38, 500, PaletteRole::PrimaryText),
            $this->text('venue', $assets, 'font-noto-arabic', "قاعة الأميرة\nطرابلس", Language::Arabic, TextDirection::RightToLeft, 400, 1580, 280, 130, 38, 500, PaletteRole::PrimaryText),
            $this->text('schedule', $assets, 'font-noto-arabic', "غداء وقراءة\nالفاتحة", Language::Arabic, TextDirection::RightToLeft, 118, 1580, 280, 130, 38, 500, PaletteRole::PrimaryText),
            $this->text('closing', $assets, 'font-aref-ruqaa', 'أفراحنا تزدان بحضوركم', Language::Arabic, TextDirection::RightToLeft, 140, 1775, 800, 110, 66, 400, PaletteRole::Accent),
        ];

        return $this->document(
            $layers,
            new PaletteData(1, '#eadcc0', '#f4ead5', '#3d3d22', '#5a5838', '#7a6a3e', '#c9b88c'),
            $this->envelopeScene('#d9c9a3', 'ق', false, false),
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

    private function shape(string $id, float $x, float $y, float $width, float $height, PaletteRole $fill): LayerData
    {
        return new LayerData(
            $id,
            LayerType::Shape,
            new LayerFrameData($x, $y, $width, $height, 1, 1, 0, 1),
            true,
            true,
            new ShapeLayerData(ShapeKind::Rectangle, ColorValueData::palette($fill), null, 0),
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
