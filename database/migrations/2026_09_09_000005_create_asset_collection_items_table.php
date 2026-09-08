<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_collection_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('collection_id')->constrained('asset_collections')->cascadeOnDelete();
            $table->foreignId('template_asset_id')->constrained('template_assets')->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->json('placement_json');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['collection_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_collection_items');
    }
};
