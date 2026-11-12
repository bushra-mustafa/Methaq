<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Editor\Models\TemplateAsset;
use App\Domains\Editor\Services\TemplateDesignFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

final class InvitationAudioSeeder extends Seeder
{
    public const HENNA_CELEBRATION_SLUG = TemplateDesignFactory::HENNA_AUDIO_SLUG;

    public function run(): void
    {
        $this->seedTrack('audio-methaq-chime', 'نغمة ميثاق الهادئة', 'methaq-chime.wav', 'audio/wav', 12, [
            'source' => 'original-synthesis',
            'redistribution' => true,
        ]);

        $this->seedTrack(self::HENNA_CELEBRATION_SLUG, 'احتفال ليلة الحنّة', 'henna-celebration.mp3', 'audio/mpeg', 79, [
            'source' => 'owner-provided',
            'license' => 'owner-provided',
            'commercialUse' => true,
            'redistribution' => true,
        ]);
    }

    /** @param array<string, string|bool> $license */
    private function seedTrack(string $slug, string $name, string $file, string $mimeType, int $durationSeconds, array $license): void
    {
        $previewPath = "brand/audio/{$file}";
        $originalPath = "editor/audio/{$file}";
        Storage::disk('local')->put($originalPath, file_get_contents(public_path($previewPath)));

        TemplateAsset::query()->firstOrCreate(['slug' => $slug], [
            'name' => $name, 'type' => 'audio',
            'original_path' => $originalPath, 'preview_path' => $previewPath,
            'mime_type' => $mimeType, 'capabilities' => [],
            'metadata' => ['durationSeconds' => $durationSeconds],
            'license_metadata' => $license,
            'asset_version' => 1, 'is_active' => true,
        ]);
    }
}
