<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_designs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->unique('event_id');
            $table->json('design_json');
            $table->json('scene_json');
            $table->json('palette_json');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->unsignedInteger('revision')->default(1);
            $table->string('watermarked_preview_path', 1024)->nullable();
            $table->unsignedInteger('preview_revision')->nullable();
            $table->string('final_render_path', 1024)->nullable();
            $table->unsignedInteger('published_revision')->nullable();
            $table->json('published_scene_json')->nullable();
            $table->json('published_palette_json')->nullable();
            $table->dateTime('rendered_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
        });
        DB::statement('ALTER TABLE `event_designs` ADD CONSTRAINT `designs_revision_check` CHECK (revision > 0 AND schema_version > 0)');
        DB::statement('ALTER TABLE `event_designs` ADD CONSTRAINT `designs_preview_check` CHECK ((preview_revision IS NULL AND watermarked_preview_path IS NULL) OR (preview_revision IS NOT NULL AND preview_revision > 0 AND preview_revision <= revision AND watermarked_preview_path IS NOT NULL))');
        DB::statement('ALTER TABLE `event_designs` ADD CONSTRAINT `designs_published_check` CHECK ((published_revision IS NULL AND final_render_path IS NULL AND published_scene_json IS NULL AND published_palette_json IS NULL AND rendered_at IS NULL) OR (published_revision IS NOT NULL AND published_revision > 0 AND published_revision <= revision AND final_render_path IS NOT NULL AND published_scene_json IS NOT NULL AND published_palette_json IS NOT NULL AND rendered_at IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('event_designs');
    }
};
