<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Counters only: one row per day, no address, no cookie, nothing that identifies a visitor. */
    public function up(): void
    {
        Schema::create('site_visit_days', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->date('day')->primary();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);
        });

        Schema::create('event_visit_days', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('visitors')->default(0);
            $table->primary(['event_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_visit_days');
        Schema::dropIfExists('site_visit_days');
    }
};
