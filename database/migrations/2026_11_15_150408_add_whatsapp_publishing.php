<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('whatsapp_phone', 16)->nullable()->unique();
            $table->string('whatsapp_pending_phone', 16)->nullable();
            $table->timestamp('whatsapp_verified_at')->nullable();
            $table->string('whatsapp_code_hash')->nullable();
            $table->timestamp('whatsapp_code_expires_at')->nullable();
            $table->timestamp('whatsapp_code_sent_at')->nullable();
            $table->unsignedTinyInteger('whatsapp_code_attempts')->default(0);
        });
        Schema::table('event_designs', function (Blueprint $table): void {
            $table->string('prepared_render_path', 1024)->nullable();
            $table->unsignedInteger('prepared_revision')->nullable();
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('whatsapp_phone', 16)->nullable();
        });
        Schema::create('whatsapp_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('deduplication_key', 128)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 16);
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider_message_id')->nullable()->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('expires_at');
            $table->string('failure_code', 80)->nullable();
            $table->timestamps();
        });
        Schema::create('cloud_msg_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_event_id', 128)->unique();
            $table->longText('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_msg_webhooks');
        Schema::dropIfExists('whatsapp_messages');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('whatsapp_phone'));
        Schema::table('event_designs', fn (Blueprint $table) => $table->dropColumn(['prepared_render_path', 'prepared_revision']));
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['whatsapp_phone']);
            $table->dropColumn(['whatsapp_phone', 'whatsapp_pending_phone', 'whatsapp_verified_at', 'whatsapp_code_hash', 'whatsapp_code_expires_at', 'whatsapp_code_sent_at', 'whatsapp_code_attempts']);
        });
    }
};
