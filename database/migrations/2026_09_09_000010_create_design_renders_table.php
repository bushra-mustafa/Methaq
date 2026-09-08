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
        Schema::create('design_renders', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('event_design_id')->constrained('event_designs')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->string('kind', 20)->charset('ascii')->collation('ascii_bin');
            $table->json('design_snapshot');
            $table->string('status', 20)->charset('ascii')->collation('ascii_bin')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('available_at', 6);
            $table->dateTime('locked_until', 6)->nullable();
            $table->string('output_path', 1024)->nullable();
            $table->dateTime('completed_at', 6)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['event_design_id', 'revision', 'kind']);
            $table->index(['status', 'available_at']);
            $table->index(['status', 'locked_until']);
        });
        DB::statement('ALTER TABLE `design_renders` ADD CONSTRAINT `renders_revision_check` CHECK (revision > 0)');
        DB::statement("ALTER TABLE `design_renders` ADD CONSTRAINT `renders_kind_check` CHECK (kind IN ('preview', 'final'))");
        DB::statement("ALTER TABLE `design_renders` ADD CONSTRAINT `renders_status_check` CHECK (status IN ('pending', 'processing', 'completed', 'failed'))");
        DB::statement("ALTER TABLE `design_renders` ADD CONSTRAINT `renders_completed_check` CHECK (status <> 'completed' OR (output_path IS NOT NULL AND completed_at IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('design_renders');
    }
};
