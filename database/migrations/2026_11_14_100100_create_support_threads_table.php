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
        Schema::create('support_threads', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('topic', 20)->charset('ascii')->collation('ascii_bin');
            $table->string('subject', 120);
            $table->string('status', 10)->charset('ascii')->collation('ascii_bin')->default('open');
            $table->boolean('admin_unread')->default(true);
            $table->boolean('customer_unread')->default(false);
            $table->dateTime('last_message_at', 6);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_message_at']);
        });
        DB::statement("ALTER TABLE `support_threads` ADD CONSTRAINT `support_threads_topic_check` CHECK (topic IN ('problem', 'custom_design'))");
        DB::statement("ALTER TABLE `support_threads` ADD CONSTRAINT `support_threads_status_check` CHECK (status IN ('open', 'closed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('support_threads');
    }
};
