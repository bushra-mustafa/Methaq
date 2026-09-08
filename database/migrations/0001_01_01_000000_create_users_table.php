<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Methaq migrations require MySQL 8.0.16 or later.');
        }

        $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;
        if (str_contains($version, 'MariaDB') || version_compare($version, '8.0.16', '<')) {
            throw new RuntimeException('Methaq requires enforced CHECK constraints on MySQL 8.0.16 or later.');
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->engine = 'InnoDB';
            $table->string('name', 120);
            $table->string('email', 254)->unique();
            $table->dateTime('email_verified_at', 6)->nullable();
            $table->string('password');
            $table->rememberToken();

            $table->string('phone', 32)->nullable();
            $table->string('role', 20)->charset('ascii')->collation('ascii_bin')->default('customer');
            $table->string('status', 20)->charset('ascii')->collation('ascii_bin')->default('active');
            $table->dateTime('suspended_at', 6)->nullable();
            $table->string('suspension_reason', 500)->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->dateTime('two_factor_confirmed_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['role', 'status']);
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('customer', 'admin'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active', 'suspended'))");

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
