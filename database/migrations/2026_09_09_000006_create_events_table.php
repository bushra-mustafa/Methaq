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
        Schema::create('events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->restrictOnDelete();
            $table->string('category', 24)->charset('ascii')->collation('ascii_bin');
            $table->string('title', 200);
            $table->string('subdomain', 63)->charset('ascii')->collation('ascii_bin')->unique();
            $table->dateTime('event_date', 6);
            $table->string('timezone', 64);
            $table->dateTime('expires_at', 6);
            $table->string('status', 20)->charset('ascii')->collation('ascii_bin')->default('draft');
            $table->boolean('is_paid')->default(false);
            $table->dateTime('paid_at', 6)->nullable();
            $table->dateTime('published_at', 6)->nullable();
            $table->dateTime('suspended_at', 6)->nullable();
            $table->string('suspension_reason', 500)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'expires_at']);
            $table->unique(['id', 'user_id']);
        });
        DB::statement("ALTER TABLE `events` ADD CONSTRAINT `events_category_check` CHECK (category IN ('wedding', 'henna', 'marriage_contract', 'graduation'))");
        DB::statement("ALTER TABLE `events` ADD CONSTRAINT `events_status_check` CHECK (status IN ('draft', 'published', 'expired'))");
        DB::statement('ALTER TABLE `events` ADD CONSTRAINT `events_expiry_check` CHECK (expires_at > event_date)');
        DB::statement('ALTER TABLE `events` ADD CONSTRAINT `events_paid_check` CHECK ((is_paid = 0 AND paid_at IS NULL) OR (is_paid = 1 AND paid_at IS NOT NULL))');
        DB::statement("ALTER TABLE `events` ADD CONSTRAINT `events_publish_check` CHECK (status <> 'published' OR (is_paid = 1 AND published_at IS NOT NULL))");
        DB::statement("ALTER TABLE `events` ADD CONSTRAINT `events_subdomain_check` CHECK (REGEXP_LIKE(subdomain, '^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$', 'c'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
