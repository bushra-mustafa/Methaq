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
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('actor_type', 20)->charset('ascii')->collation('ascii_bin');
            $table->string('action', 100);
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id');
            $table->string('reason', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at', 6);
            $table->index(['actor_id', 'created_at']);
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
        DB::statement("ALTER TABLE `audit_logs` ADD CONSTRAINT `audit_actor_check` CHECK ((actor_type = 'user' AND actor_id IS NOT NULL) OR (actor_type = 'system' AND actor_id IS NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
