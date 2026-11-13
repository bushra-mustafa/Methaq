<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const WITH_NEW = "'wedding', 'henna', 'marriage_contract', 'graduation', 'project_opening', 'newborn'";

    private const ORIGINAL = "'wedding', 'henna', 'marriage_contract', 'graduation'";

    public function up(): void
    {
        $this->replace('templates', 'templates_category_check', self::WITH_NEW);
        $this->replace('events', 'events_category_check', self::WITH_NEW);
    }

    public function down(): void
    {
        $this->replace('templates', 'templates_category_check', self::ORIGINAL);
        $this->replace('events', 'events_category_check', self::ORIGINAL);
    }

    private function replace(string $table, string $constraint, string $categories): void
    {
        DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$constraint}`");
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` CHECK (category IN ({$categories}))");
    }
};
