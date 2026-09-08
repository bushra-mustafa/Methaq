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
        Schema::create('payment_webhooks', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('gateway', 20)->charset('ascii')->collation('ascii_bin');
            $table->string('gateway_event_id', 191)->charset('ascii')->collation('ascii_bin');
            $table->foreignId('order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->string('event_type', 100);
            $table->json('payload');
            $table->string('status', 20)->charset('ascii')->collation('ascii_bin')->default('received');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('received_at', 6);
            $table->dateTime('locked_until', 6)->nullable();
            $table->dateTime('processed_at', 6)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['gateway', 'gateway_event_id']);
            $table->index(['status', 'locked_until']);
        });
        DB::statement("ALTER TABLE `payment_webhooks` ADD CONSTRAINT `webhooks_gateway_check` CHECK (gateway IN ('stripe', 'tap'))");
        DB::statement("ALTER TABLE `payment_webhooks` ADD CONSTRAINT `webhooks_status_check` CHECK (status IN ('received', 'processing', 'processed', 'failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};
