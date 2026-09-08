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
        Schema::create('orders', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('event_id');
            $table->foreign(['event_id', 'user_id'])->references(['id', 'user_id'])->on('events')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->charset('ascii')->collation('ascii_bin');
            $table->string('status', 20)->charset('ascii')->collation('ascii_bin')->default('pending');
            $table->string('gateway', 20)->charset('ascii')->collation('ascii_bin');
            $table->string('transaction_id', 191)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('checkout_id', 191)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('idempotency_key', 36)->charset('ascii')->collation('ascii_bin')->unique();
            $table->dateTime('completed_at', 6)->nullable();
            $table->string('failure_code', 100)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['gateway', 'transaction_id']);
            $table->unique(['gateway', 'checkout_id']);
            $table->index(['event_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_status_check` CHECK (status IN ('pending', 'completed', 'failed'))");
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_gateway_check` CHECK (gateway IN ('stripe', 'tap'))");
        DB::statement('ALTER TABLE `orders` ADD CONSTRAINT `orders_amount_check` CHECK (amount > 0)');
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_currency_check` CHECK (REGEXP_LIKE(currency, '^[A-Z]{3}$', 'c'))");
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_completed_check` CHECK (status <> 'completed' OR (completed_at IS NOT NULL AND transaction_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
