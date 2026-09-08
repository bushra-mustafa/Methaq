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
        Schema::create('template_assets', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 150);
            $table->string('type', 20)->charset('ascii')->collation('ascii_bin');
            $table->string('original_path', 1024);
            $table->string('preview_path', 1024);
            $table->string('mime_type', 100);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('metadata')->nullable();
            $table->json('capabilities');
            $table->json('license_metadata')->nullable();
            $table->unsignedInteger('asset_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['type', 'is_active']);
        });
        DB::statement("ALTER TABLE `template_assets` ADD CONSTRAINT `assets_type_check` CHECK (type IN ('background', 'frame', 'icon', 'font', 'decoration', 'audio'))");
        DB::statement('ALTER TABLE `template_assets` ADD CONSTRAINT `assets_version_check` CHECK (asset_version > 0)');
        DB::statement("ALTER TABLE `template_assets` ADD CONSTRAINT `assets_dimensions_check` CHECK (type IN ('font', 'audio') OR (width IS NOT NULL AND width > 0 AND height IS NOT NULL AND height > 0))");
    }

    public function down(): void
    {
        Schema::dropIfExists('template_assets');
    }
};
