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
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->string('kind', 24)->default('notification');
            $table->foreignId('resend_of_id')->nullable()->unique()->constrained('whatsapp_messages')->restrictOnDelete();
        });
        foreach (['verify-' => 'verification', 'receipt-' => 'receipt', 'payment-approved-' => 'approval', 'invitation-published-' => 'publication', 'reply-' => 'reply'] as $prefix => $kind) {
            DB::table('whatsapp_messages')->where('deduplication_key', 'like', $prefix.'%')->update(['kind' => $kind]);
        }
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropForeign(['resend_of_id']);
            $table->dropUnique(['resend_of_id']);
            $table->dropColumn(['kind', 'resend_of_id']);
        });
    }
};
