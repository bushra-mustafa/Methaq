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
        Schema::create('templates', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 150);
            $table->string('category', 24)->charset('ascii')->collation('ascii_bin');
            $table->string('thumbnail_path', 1024);
            $table->json('default_design_json');
            $table->json('default_scene_json');
            $table->json('default_palette_json');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['category', 'is_active']);
        });
        DB::statement("ALTER TABLE `templates` ADD CONSTRAINT `templates_category_check` CHECK (category IN ('wedding', 'henna', 'marriage_contract', 'graduation'))");
        DB::statement('ALTER TABLE `templates` ADD CONSTRAINT `templates_schema_version_check` CHECK (schema_version > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
