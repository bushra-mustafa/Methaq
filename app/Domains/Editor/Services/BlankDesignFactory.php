<?php

declare(strict_types=1);

namespace App\Domains\Editor\Services;

use App\Domains\Editor\Contracts\DesignSchema;
use App\Domains\Editor\DTOs\AudioData;
use App\Domains\Editor\DTOs\CanvasData;
use App\Domains\Editor\DTOs\ColorValueData;
use App\Domains\Editor\DTOs\DesignDocumentData;
use App\Domains\Editor\DTOs\OpeningData;
use App\Domains\Editor\DTOs\PaletteData;
use App\Domains\Editor\DTOs\SceneConfigData;
use App\Domains\Editor\Enums\MotionPolicy;
use App\Domains\Editor\Enums\OpeningType;
use App\Domains\Editor\Enums\PaletteRole;

final class BlankDesignFactory
{
    public function make(): DesignDocumentData
    {
        return new DesignDocumentData(
            DesignSchema::VERSION,
            new CanvasData(
                DesignSchema::CANVAS_VERSION,
                DesignSchema::CANVAS_WIDTH,
                DesignSchema::CANVAS_HEIGHT,
                ColorValueData::palette(PaletteRole::Background),
                [],
            ),
            new PaletteData(DesignSchema::PALETTE_VERSION, '#f5f2e3', '#ffffff', '#074b36', '#315e50', '#b69a50', '#d8c680'),
            new SceneConfigData(
                DesignSchema::SCENE_VERSION,
                new OpeningData(OpeningType::Direct, 0),
                [],
                new AudioData(false, null, 0.6),
                MotionPolicy::System,
            ),
        );
    }
}
