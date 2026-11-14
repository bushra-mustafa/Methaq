<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A limited free offer: a number of customers may publish without paying within a window of time.
 * A claim becomes a zero-amount completed order (gateway free_offer), so entitlements and publishing work as for a paid event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_offers', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title', 120);
            $table->string('description', 300)->nullable();
            $table->foreignId('template_id')->nullable()->constrained('templates')->restrictOnDelete();
            $table->unsignedSmallInteger('max_claims');
            $table->dateTime('starts_at', 6);
            $table->dateTime('ends_at', 6);
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['is_active', 'ends_at']);
        });
        DB::statement('ALTER TABLE `free_offers` ADD CONSTRAINT `free_offers_window_check` CHECK (ends_at > starts_at AND max_claims >= 1)');

        Schema::create('free_offer_claims', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('free_offer_id')->constrained('free_offers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->dateTime('created_at', 6);
            $table->unique(['free_offer_id', 'user_id']);
            $table->unique('event_id');
        });

        DB::statement('ALTER TABLE `orders` DROP CHECK `orders_gateway_check`');
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_gateway_check` CHECK (gateway IN ('stripe', 'tap', 'manual_transfer', 'free_offer'))");
        DB::statement('ALTER TABLE `orders` DROP CHECK `orders_amount_check`');
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_amount_check` CHECK (amount > 0 OR (gateway = 'free_offer' AND amount = 0))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `orders` DROP CHECK `orders_amount_check`');
        DB::statement('ALTER TABLE `orders` ADD CONSTRAINT `orders_amount_check` CHECK (amount > 0)');
        DB::statement('ALTER TABLE `orders` DROP CHECK `orders_gateway_check`');
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_gateway_check` CHECK (gateway IN ('stripe', 'tap', 'manual_transfer'))");
        Schema::dropIfExists('free_offer_claims');
        Schema::dropIfExists('free_offers');
    }
};
