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
        Schema::create('rsvps', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->string('submission_token', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('guest_name', 120);
            $table->string('guest_phone', 32)->nullable();
            $table->string('attending_status', 20)->charset('ascii')->collation('ascii_bin');
            $table->unsignedSmallInteger('companions_count')->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['event_id', 'submission_token']);
            $table->index(['event_id', 'attending_status']);
        });
        DB::statement("ALTER TABLE `rsvps` ADD CONSTRAINT `rsvps_status_check` CHECK (attending_status IN ('attending', 'declined', 'maybe'))");
        DB::statement("ALTER TABLE `rsvps` ADD CONSTRAINT `rsvps_declined_check` CHECK (attending_status <> 'declined' OR companions_count = 0)");
        DB::statement('ALTER TABLE `rsvps` ADD CONSTRAINT `rsvps_notes_check` CHECK (notes IS NULL OR CHAR_LENGTH(notes) <= 1000)');
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};
