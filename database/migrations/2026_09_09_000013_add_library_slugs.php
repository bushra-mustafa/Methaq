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
        $this->addSlug('templates', 'template');
        $this->addSlug('template_assets', 'asset');
        $this->addSlug('asset_collections', 'collection');
    }

    public function down(): void
    {
        foreach (['asset_collections', 'template_assets', 'templates'] as $table) {
            DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$table}_slug_check`");
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropUnique($blueprint->getTable().'_slug_unique');
                $blueprint->dropColumn('slug');
            });
        }
    }

    private function addSlug(string $table, string $legacyPrefix): void
    {
        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->string('slug', 80)->charset('ascii')->collation('ascii_bin')->nullable()->after('id');
        });

        DB::table($table)->orderBy('id')->eachById(function (object $record) use ($legacyPrefix, $table): void {
            DB::table($table)->where('id', $record->id)->update([
                'slug' => $legacyPrefix.'-'.$record->id,
            ]);
        });

        DB::statement("ALTER TABLE `{$table}` MODIFY `slug` VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL");
        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->unique('slug', $table.'_slug_unique');
        });
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_slug_check` CHECK (slug REGEXP '^[a-z0-9]+(-[a-z0-9]+)*$')");
    }
};
