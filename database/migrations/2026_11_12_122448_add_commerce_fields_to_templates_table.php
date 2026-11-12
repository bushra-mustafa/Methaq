<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-template price (Libyan dinar minor units: 1 LYD = 1000 dirhams), library order
 * and the list of features locked for invitations made from the template.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->unsignedInteger('price_minor')->default(0)->after('is_featured');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('price_minor');
            $table->json('locked_features')->nullable()->after('sort_order');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'sort_order']);
            $table->dropColumn(['price_minor', 'sort_order', 'locked_features']);
        });
    }
};
