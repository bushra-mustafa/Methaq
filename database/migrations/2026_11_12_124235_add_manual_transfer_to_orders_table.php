<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manual bank transfer: the customer uploads a receipt (kept on the private disk),
 * an administrator approves or rejects it. Entitlements snapshot the template's
 * unlocked features at order time so later template changes never alter a paid order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('receipt_path', 255)->nullable()->after('failure_code');
            $table->char('receipt_sha256', 64)->charset('ascii')->collation('ascii_bin')->nullable()->after('receipt_path');
            $table->json('entitlements')->nullable()->after('receipt_sha256');
            $table->foreignId('reviewed_by')->nullable()->after('entitlements')->constrained('users')->restrictOnDelete();
            $table->dateTime('reviewed_at', 6)->nullable()->after('reviewed_by');
            $table->index('receipt_sha256');
            $table->index(['gateway', 'status', 'created_at']);
        });

        DB::statement('ALTER TABLE `orders` DROP CHECK `orders_gateway_check`');
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_gateway_check` CHECK (gateway IN ('stripe', 'tap', 'manual_transfer'))");
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_receipt_check` CHECK (gateway <> 'manual_transfer' OR (receipt_path IS NOT NULL AND receipt_sha256 IS NOT NULL))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `orders` DROP CHECK `orders_receipt_check`');
        DB::statement('ALTER TABLE `orders` DROP CHECK `orders_gateway_check`');
        DB::statement("ALTER TABLE `orders` ADD CONSTRAINT `orders_gateway_check` CHECK (gateway IN ('stripe', 'tap'))");

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['gateway', 'status', 'created_at']);
            $table->dropIndex(['receipt_sha256']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['receipt_path', 'receipt_sha256', 'entitlements', 'reviewed_at']);
        });
    }
};
