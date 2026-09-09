<?php

declare(strict_types=1);

namespace App\Domains\Editor\Contracts;

final class DesignSchema
{
    public const int VERSION = 1;

    public const int CANVAS_VERSION = 1;

    public const int SCENE_VERSION = 1;

    public const int PALETTE_VERSION = 1;

    public const int CANVAS_WIDTH = 1080;

    public const int CANVAS_HEIGHT = 1920;

    public const int MAXIMUM_CANVAS_DIMENSION = 4096;

    public const int MAXIMUM_JSON_BYTES = 1_048_576;

    public const int MAXIMUM_LAYERS = 200;

    public const int MAXIMUM_TEXT_LENGTH = 2000;

    public const int MAXIMUM_DEPTH = 12;

    public const int MAXIMUM_EFFECTS = 2;

    public const int MAXIMUM_ASSET_VERSION = 65_535;

    /** @var array<string, array{version: int, intensity: array{float, float}, speed: array{float, float}}> */
    public const array EFFECTS = [
        'sparkle' => ['version' => 1, 'intensity' => [0.0, 1.0], 'speed' => [0.25, 2.0]],
        'smoke' => ['version' => 1, 'intensity' => [0.0, 0.8], 'speed' => [0.25, 1.5]],
    ];

    /** @var array<string, array{version: int, maximum_monogram_length: int}> */
    public const array ENVELOPES = [
        'classic-fold' => ['version' => 1, 'maximum_monogram_length' => 12],
    ];

    private function __construct() {}
}
