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
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('bank_name', 80);
            $table->string('account_name', 120);
            $table->string('account_number', 40)->nullable();
            $table->string('iban', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['is_active', 'sort_order']);
        });
        DB::statement('ALTER TABLE `bank_accounts` ADD CONSTRAINT `bank_accounts_number_check` CHECK (account_number IS NOT NULL OR iban IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
