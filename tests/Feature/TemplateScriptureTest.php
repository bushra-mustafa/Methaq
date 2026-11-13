<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\TemplateScripture;
use Database\Seeders\TemplateLibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TemplateScriptureTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Template integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_template_verses_are_identical_to_the_editor_suggestion_list(): void
    {
        $source = (string) file_get_contents(resource_path('js/Domains/Editor/Services/textSuggestions.ts'));
        preg_match_all('/id: \'(verse-[\d-]+p?)\'.*?text: "([^"]+)", source: "([^"]+)"/u', $source, $matches, PREG_SET_ORDER);

        $this->assertCount(count(TemplateScripture::VERSES), $matches);
        foreach ($matches as [, $id, $text, $reference]) {
            $this->assertSame(['text' => $text, 'source' => $reference], TemplateScripture::VERSES[$id] ?? null, $id);
        }
    }

    public function test_every_library_template_carries_a_verse_and_only_known_verses(): void
    {
        $this->seed(TemplateLibrarySeeder::class);
        $known = array_map(static fn (string $id): string => TemplateScripture::content($id), array_keys(TemplateScripture::VERSES));

        foreach (Template::query()->get() as $template) {
            $verses = collect($template->default_design_json['layers'])
                ->filter(static fn (array $layer): bool => $layer['type'] === 'text' && str_starts_with($layer['content'], '﴿'))
                ->pluck('content');

            $this->assertGreaterThanOrEqual(1, $verses->count(), "{$template->slug} needs a verse");
            foreach ($verses as $content) {
                $this->assertContains($content, $known, "{$template->slug} quotes a verse that is not in the library");
            }
        }
    }
}
