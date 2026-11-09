<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('template_key', 80);
            $table->json('document');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'template_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_proposals');
    }
};
