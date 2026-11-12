<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Actions\ListFeaturedTemplatesAction;
use App\Domains\Editor\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Home page integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_home_shows_only_active_templates_the_admin_featured(): void
    {
        $notFeatured = Template::factory()->create(['is_active' => true, 'is_featured' => false]);
        $inactive = Template::factory()->create(['is_active' => false, 'is_featured' => true]);
        Template::factory()->count(ListFeaturedTemplatesAction::LIMIT + 1)->create(['is_active' => true, 'is_featured' => true]);
        $hidden = [(string) $notFeatured->getKey(), (string) $inactive->getKey()];

        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Web/Home')
                ->has('featuredTemplates', ListFeaturedTemplatesAction::LIMIT)
                ->where('featuredTemplates', fn ($templates): bool => collect($templates)
                    ->every(fn (array $template): bool => $template['isFeatured'] === true && ! in_array($template['id'], $hidden, true))));
    }

    public function test_privacy_policy_page_is_public(): void
    {
        $this->withoutVite()
            ->get('/privacy')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Web/Privacy'));
    }
}
