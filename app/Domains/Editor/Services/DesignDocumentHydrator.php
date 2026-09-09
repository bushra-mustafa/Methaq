<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

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
use App\Domains\Editor\DTOs\ShapeLayerData;
use App\Domains\Editor\DTOs\TextLayerData;
use App\Domains\Editor\Enums\ColorSource;
use App\Domains\Editor\Enums\EffectId;
use App\Domains\Editor\Enums\EnvelopePresetId;
use App\Domains\Editor\Enums\ImageFit;
use App\Domains\Editor\Enums\Language;
use App\Domains\Editor\Enums\LayerType;
use App\Domains\Editor\Enums\MotionPolicy;
use App\Domains\Editor\Enums\OpeningType;
use App\Domains\Editor\Enums\PaletteRole;
use App\Domains\Editor\Enums\ShapeKind;
use App\Domains\Editor\Enums\TextAlignment;
use App\Domains\Editor\Enums\TextDirection;
use BackedEnum;
use InvalidArgumentException;

final class DesignDocumentHydrator
{
    /** @param array<string, mixed> $payload */
    public function hydrate(array $payload): DesignDocumentData
    {
        $this->assertKeys($payload, ['schemaVersion', 'canvas', 'palette', 'scene'], 'document');

        return new DesignDocumentData(
            schemaVersion: $this->integer($payload['schemaVersion'], 'document.schemaVersion'),
            canvas: $this->canvas($payload['canvas']),
            palette: $this->palette($payload['palette']),
            scene: $this->scene($payload['scene']),
        );
    }

    private function canvas(mixed $value): CanvasData
    {
        $canvas = $this->object($value, 'document.canvas');
        $this->assertKeys($canvas, ['schemaVersion', 'width', 'height', 'background', 'layers'], 'document.canvas');
        $layers = $canvas['layers'];
        if (! is_array($layers) || ! array_is_list($layers)) {
            throw new InvalidArgumentException('document.canvas.layers must be a list.');
        }

        return new CanvasData(
            schemaVersion: $this->integer($canvas['schemaVersion'], 'document.canvas.schemaVersion'),
            width: $this->integer($canvas['width'], 'document.canvas.width'),
            height: $this->integer($canvas['height'], 'document.canvas.height'),
            background: $this->color($canvas['background'], 'document.canvas.background'),
            layers: array_map(fn (mixed $layer, int $index): LayerData => $this->layer($layer, $index), $layers, array_keys($layers)),
        );
    }

    private function layer(mixed $value, int $index): LayerData
    {
        $path = "document.canvas.layers.{$index}";
        $layer = $this->object($value, $path);
        $type = $this->enum($layer['type'] ?? null, LayerType::class, "{$path}.type");
        $specificKeys = match ($type) {
            LayerType::Text => ['content', 'language', 'direction', 'alignment', 'font', 'fontSize', 'fontWeight', 'fill'],
            LayerType::Image => ['asset', 'fit'],
            LayerType::Shape => ['shapeKind', 'fill', 'stroke', 'strokeWidth'],
        };
        $this->assertKeys($layer, ['id', 'type', 'frame', 'locked', 'visible', ...$specificKeys], $path);

        $properties = match ($type) {
            LayerType::Text => new TextLayerData(
                content: $this->string($layer['content'], "{$path}.content"),
                language: $this->enum($layer['language'], Language::class, "{$path}.language"),
                direction: $this->enum($layer['direction'], TextDirection::class, "{$path}.direction"),
                alignment: $this->enum($layer['alignment'], TextAlignment::class, "{$path}.alignment"),
                font: $this->asset($layer['font'], "{$path}.font"),
                fontSize: $this->number($layer['fontSize'], "{$path}.fontSize"),
                fontWeight: $this->integer($layer['fontWeight'], "{$path}.fontWeight"),
                fill: $this->color($layer['fill'], "{$path}.fill"),
            ),
            LayerType::Image => new ImageLayerData(
                asset: $this->asset($layer['asset'], "{$path}.asset"),
                fit: $this->enum($layer['fit'], ImageFit::class, "{$path}.fit"),
            ),
            LayerType::Shape => new ShapeLayerData(
                shapeKind: $this->enum($layer['shapeKind'], ShapeKind::class, "{$path}.shapeKind"),
                fill: $layer['fill'] === null ? null : $this->color($layer['fill'], "{$path}.fill"),
                stroke: $layer['stroke'] === null ? null : $this->color($layer['stroke'], "{$path}.stroke"),
                strokeWidth: $this->number($layer['strokeWidth'], "{$path}.strokeWidth"),
            ),
        };

        return new LayerData(
            id: $this->string($layer['id'], "{$path}.id"),
            type: $type,
            frame: $this->frame($layer['frame'], "{$path}.frame"),
            locked: $this->boolean($layer['locked'], "{$path}.locked"),
            visible: $this->boolean($layer['visible'], "{$path}.visible"),
            properties: $properties,
        );
    }

    private function frame(mixed $value, string $path): LayerFrameData
    {
        $frame = $this->object($value, $path);
        $this->assertKeys($frame, ['x', 'y', 'width', 'height', 'scaleX', 'scaleY', 'rotation', 'opacity'], $path);

        return new LayerFrameData(
            x: $this->number($frame['x'], "{$path}.x"),
            y: $this->number($frame['y'], "{$path}.y"),
            width: $this->number($frame['width'], "{$path}.width"),
            height: $this->number($frame['height'], "{$path}.height"),
            scaleX: $this->number($frame['scaleX'], "{$path}.scaleX"),
            scaleY: $this->number($frame['scaleY'], "{$path}.scaleY"),
            rotation: $this->number($frame['rotation'], "{$path}.rotation"),
            opacity: $this->number($frame['opacity'], "{$path}.opacity"),
        );
    }

    private function palette(mixed $value): PaletteData
    {
        $palette = $this->object($value, 'document.palette');
        $this->assertKeys($palette, ['schemaVersion', 'values'], 'document.palette');
        $values = $this->object($palette['values'], 'document.palette.values');
        $this->assertKeys($values, ['background', 'surface', 'primaryText', 'secondaryText', 'accent', 'effect'], 'document.palette.values');

        return new PaletteData(
            schemaVersion: $this->integer($palette['schemaVersion'], 'document.palette.schemaVersion'),
            background: $this->string($values['background'], 'document.palette.values.background'),
            surface: $this->string($values['surface'], 'document.palette.values.surface'),
            primaryText: $this->string($values['primaryText'], 'document.palette.values.primaryText'),
            secondaryText: $this->string($values['secondaryText'], 'document.palette.values.secondaryText'),
            accent: $this->string($values['accent'], 'document.palette.values.accent'),
            effect: $this->string($values['effect'], 'document.palette.values.effect'),
        );
    }

    private function scene(mixed $value): SceneConfigData
    {
        $scene = $this->object($value, 'document.scene');
        $this->assertKeys($scene, ['sceneSchemaVersion', 'opening', 'effects', 'audio', 'motionPolicy'], 'document.scene');
        $effects = $scene['effects'];
        if (! is_array($effects) || ! array_is_list($effects)) {
            throw new InvalidArgumentException('document.scene.effects must be a list.');
        }

        return new SceneConfigData(
            sceneSchemaVersion: $this->integer($scene['sceneSchemaVersion'], 'document.scene.sceneSchemaVersion'),
            opening: $this->opening($scene['opening']),
            effects: array_map(fn (mixed $effect, int $index): EffectData => $this->effect($effect, $index), $effects, array_keys($effects)),
            audio: $this->audio($scene['audio']),
            motionPolicy: $this->enum($scene['motionPolicy'], MotionPolicy::class, 'document.scene.motionPolicy'),
        );
    }

    private function opening(mixed $value): OpeningData
    {
        $opening = $this->object($value, 'document.scene.opening');
        $type = $this->enum($opening['type'] ?? null, OpeningType::class, 'document.scene.opening.type');
        $keys = $type === OpeningType::Envelope ? ['type', 'durationMs', 'envelope'] : ['type', 'durationMs'];
        $this->assertKeys($opening, $keys, 'document.scene.opening');

        return new OpeningData(
            type: $type,
            durationMs: $this->integer($opening['durationMs'], 'document.scene.opening.durationMs'),
            envelope: $type === OpeningType::Envelope ? $this->envelope($opening['envelope']) : null,
        );
    }

    private function envelope(mixed $value): EnvelopeData
    {
        $path = 'document.scene.opening.envelope';
        $envelope = $this->object($value, $path);
        $this->assertKeys($envelope, ['presetId', 'presetVersion', 'paperColor', 'liningColor', 'sealColor', 'monogram'], $path);

        return new EnvelopeData(
            presetId: $this->enum($envelope['presetId'], EnvelopePresetId::class, "{$path}.presetId"),
            presetVersion: $this->integer($envelope['presetVersion'], "{$path}.presetVersion"),
            paperColor: $this->color($envelope['paperColor'], "{$path}.paperColor"),
            liningColor: $this->color($envelope['liningColor'], "{$path}.liningColor"),
            sealColor: $this->color($envelope['sealColor'], "{$path}.sealColor"),
            monogram: $this->string($envelope['monogram'], "{$path}.monogram"),
        );
    }

    private function effect(mixed $value, int $index): EffectData
    {
        $path = "document.scene.effects.{$index}";
        $effect = $this->object($value, $path);
        $this->assertKeys($effect, ['effectId', 'effectVersion', 'enabled', 'color', 'intensity', 'speed'], $path);

        return new EffectData(
            effectId: $this->enum($effect['effectId'], EffectId::class, "{$path}.effectId"),
            effectVersion: $this->integer($effect['effectVersion'], "{$path}.effectVersion"),
            enabled: $this->boolean($effect['enabled'], "{$path}.enabled"),
            color: $this->color($effect['color'], "{$path}.color"),
            intensity: $this->number($effect['intensity'], "{$path}.intensity"),
            speed: $this->number($effect['speed'], "{$path}.speed"),
        );
    }

    private function audio(mixed $value): AudioData
    {
        $path = 'document.scene.audio';
        $audio = $this->object($value, $path);
        $this->assertKeys($audio, ['enabled', 'asset', 'volume'], $path);

        return new AudioData(
            enabled: $this->boolean($audio['enabled'], "{$path}.enabled"),
            asset: $audio['asset'] === null ? null : $this->asset($audio['asset'], "{$path}.asset"),
            volume: $this->number($audio['volume'], "{$path}.volume"),
        );
    }

    private function color(mixed $value, string $path): ColorValueData
    {
        $color = $this->object($value, $path);
        $source = $this->enum($color['source'] ?? null, ColorSource::class, "{$path}.source");
        $this->assertKeys($color, $source === ColorSource::Palette ? ['source', 'role'] : ['source', 'value'], $path);

        return $source === ColorSource::Palette
            ? ColorValueData::palette($this->enum($color['role'], PaletteRole::class, "{$path}.role"))
            : ColorValueData::literal($this->string($color['value'], "{$path}.value"));
    }

    private function asset(mixed $value, string $path): AssetReferenceData
    {
        $asset = $this->object($value, $path);
        $this->assertKeys($asset, ['assetId', 'version'], $path);

        return new AssetReferenceData(
            assetId: $this->string($asset['assetId'], "{$path}.assetId"),
            version: $this->integer($asset['version'], "{$path}.version"),
        );
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $path): array
    {
        if (! is_array($value) || array_is_list($value)) {
            throw new InvalidArgumentException("{$path} must be an object.");
        }

        return $value;
    }

    /** @param array<string, mixed> $value @param list<string> $keys */
    private function assertKeys(array $value, array $keys, string $path): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            throw new InvalidArgumentException("{$path} contains missing or unsupported properties.");
        }
    }

    private function string(mixed $value, string $path): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("{$path} must be a string.");
        }

        return $value;
    }

    private function integer(mixed $value, string $path): int
    {
        if (! is_int($value)) {
            throw new InvalidArgumentException("{$path} must be an integer.");
        }

        return $value;
    }

    private function number(mixed $value, string $path): float
    {
        if (! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException("{$path} must be a number.");
        }

        return (float) $value;
    }

    private function boolean(mixed $value, string $path): bool
    {
        if (! is_bool($value)) {
            throw new InvalidArgumentException("{$path} must be boolean.");
        }

        return $value;
    }

    /** @template T of BackedEnum @param class-string<T> $enum @return T */
    private function enum(mixed $value, string $enum, string $path): BackedEnum
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException("{$path} must be a supported string.");
        }

        $resolved = $enum::tryFrom($value);
        if ($resolved === null) {
            throw new InvalidArgumentException("{$path} is unsupported.");
        }

        return $resolved;
    }
}
