<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_asset_links', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->foreignId('template_asset_id')->constrained('template_assets')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['template_id', 'template_asset_id']);
            $table->index('template_asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_asset_links');
    }
};
