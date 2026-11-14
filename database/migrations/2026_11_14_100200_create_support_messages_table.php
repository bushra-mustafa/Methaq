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
        Schema::create('support_messages', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('support_thread_id')->constrained('support_threads')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->boolean('from_admin')->default(false);
            $table->text('body');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['support_thread_id', 'created_at']);
        });
        DB::statement('ALTER TABLE `support_messages` ADD CONSTRAINT `support_messages_body_check` CHECK (CHAR_LENGTH(body) BETWEEN 1 AND 3000)');
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
