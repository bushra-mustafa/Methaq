<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_channels', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 80);
            $table->string('url', 255);
            $table->string('icon', 20)->charset('ascii')->collation('ascii_bin');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_channels');
    }
};
