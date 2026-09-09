<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\DTOs\AssetReferenceData;
use App\Domains\Editor\DTOs\AudioData;
use App\Domains\Editor\DTOs\CanvasData;
use App\Domains\Editor\DTOs\ColorValueData;
use App\Domains\Editor\DTOs\DesignDocumentData;
use App\Domains\Editor\DTOs\DesignSnapshotData;
use App\Domains\Editor\DTOs\EffectData;
use App\Domains\Editor\DTOs\EnvelopeData;
use App\Domains\Editor\DTOs\LayerData;
use App\Domains\Editor\DTOs\LayerFrameData;
use App\Domains\Editor\DTOs\OpeningData;
use App\Domains\Editor\DTOs\PaletteData;
use App\Domains\Editor\DTOs\SceneConfigData;
use App\Domains\Editor\DTOs\TextLayerData;
use App\Domains\Editor\Enums\EffectId;
use App\Domains\Editor\Enums\EnvelopePresetId;
use App\Domains\Editor\Enums\Language;
use App\Domains\Editor\Enums\LayerType;
use App\Domains\Editor\Enums\MotionPolicy;
use App\Domains\Editor\Enums\OpeningType;
use App\Domains\Editor\Enums\PaletteRole;
use App\Domains\Editor\Enums\TextAlignment;
use App\Domains\Editor\Enums\TextDirection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DesignContractTest extends TestCase
{
    public function test_typed_document_maps_to_the_three_storage_sections(): void
    {
        $document = $this->document();
        $snapshot = new DesignSnapshotData($document, 8);

        self::assertSame(8, $snapshot->toArray()['revision']);
        self::assertSame(1080, $document->toArray()['canvas']['width']);
        self::assertSame('palette', $document->toArray()['canvas']['background']['source']);
        self::assertSame('classic-fold', $document->toArray()['scene']['opening']['envelope']['presetId']);
        self::assertSame(
            ['design_json', 'palette_json', 'scene_json', 'schema_version'],
            array_keys($document->toStorageColumns()),
        );
    }

    public function test_layer_type_must_match_its_typed_properties(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LayerData(
            id: 'bad_layer',
            type: LayerType::Image,
            frame: $this->frame(),
            locked: false,
            visible: true,
            properties: $this->textProperties(),
        );
    }

    public function test_scene_rejects_duplicate_effects(): void
    {
        $sparkle = $this->sparkle();
        $this->expectException(InvalidArgumentException::class);

        new SceneConfigData(
            sceneSchemaVersion: DesignSchema::SCENE_VERSION,
            opening: new OpeningData(OpeningType::Direct, 0),
            effects: [$sparkle, $sparkle],
            audio: new AudioData(false, null, 0.6),
            motionPolicy: MotionPolicy::System,
        );
    }

    private function document(): DesignDocumentData
    {
        $layer = new LayerData(
            id: 'names_1',
            type: LayerType::Text,
            frame: $this->frame(),
            locked: false,
            visible: true,
            properties: $this->textProperties(),
        );
        $envelope = new EnvelopeData(
            EnvelopePresetId::ClassicFold,
            1,
            ColorValueData::literal('#d8b0b0'),
            ColorValueData::palette(PaletteRole::Surface),
            ColorValueData::palette(PaletteRole::Accent),
            'L & A',
        );

        return new DesignDocumentData(
            schemaVersion: DesignSchema::VERSION,
            canvas: new CanvasData(
                DesignSchema::CANVAS_VERSION,
                DesignSchema::CANVAS_WIDTH,
                DesignSchema::CANVAS_HEIGHT,
                ColorValueData::palette(PaletteRole::Background),
                [$layer],
            ),
            palette: new PaletteData(1, '#ead0d0', '#fffaf1', '#866143', '#6f5949', '#a78550', '#fff5da'),
            scene: new SceneConfigData(
                sceneSchemaVersion: DesignSchema::SCENE_VERSION,
                opening: new OpeningData(OpeningType::Envelope, 1700, $envelope),
                effects: [$this->sparkle()],
                audio: new AudioData(false, null, 0.6),
                motionPolicy: MotionPolicy::System,
            ),
        );
    }

    private function frame(): LayerFrameData
    {
        return new LayerFrameData(120, 600, 840, 420, 1, 1, 0, 1);
    }

    private function textProperties(): TextLayerData
    {
        return new TextLayerData(
            content: 'ليان و آدم',
            language: Language::Arabic,
            direction: TextDirection::RightToLeft,
            alignment: TextAlignment::Center,
            font: new AssetReferenceData('4', 1),
            fontSize: 144,
            fontWeight: 400,
            fill: ColorValueData::palette(PaletteRole::PrimaryText),
        );
    }

    private function sparkle(): EffectData
    {
        return new EffectData(
            effectId: EffectId::Sparkle,
            effectVersion: 1,
            enabled: true,
            color: ColorValueData::palette(PaletteRole::Effect),
            intensity: 0.55,
            speed: 1,
        );
    }
}
