<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->unsignedInteger('design_revision')->default(1)->after('schema_version');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->dropColumn('design_revision');
        });
    }
};
