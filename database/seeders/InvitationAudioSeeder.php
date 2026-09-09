<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Editor\Models\TemplateAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

final class InvitationAudioSeeder extends Seeder
{
    public function run(): void
    {
        $path = 'brand/audio/methaq-chime.wav';
        Storage::disk('local')->put('editor/audio/methaq-chime.wav', file_get_contents(public_path($path)));
        TemplateAsset::query()->firstOrCreate(['slug' => 'audio-methaq-chime'], [
            'name' => 'نغمة ميثاق الهادئة', 'type' => 'audio',
            'original_path' => 'editor/audio/methaq-chime.wav', 'preview_path' => $path,
            'mime_type' => 'audio/wav', 'capabilities' => [],
            'metadata' => ['durationSeconds' => 12],
            'license_metadata' => ['source' => 'original-synthesis', 'redistribution' => true],
            'asset_version' => 1, 'is_active' => true,
        ]);
    }
}
