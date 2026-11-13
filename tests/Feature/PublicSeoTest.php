<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Events\Enums\EventCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('SEO tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_every_occasion_has_a_landing_page_with_its_own_head_and_only_priced_templates(): void
    {
        Template::factory()->create(['category' => EventCategory::Newborn->value, 'name' => 'مسعّر', 'price_minor' => 25_000]);
        Template::factory()->create(['category' => EventCategory::Newborn->value, 'name' => 'بلا سعر', 'price_minor' => 0]);

        foreach (EventCategory::cases() as $category) {
            $this->withoutVite()->get('/occasions/'.$category->slug())
                ->assertOk()
                ->assertSee('<meta name="description" content="'.e($category->pitch()['description']).'">', false)
                ->assertSee('<link rel="canonical" href="'.url('/occasions/'.$category->slug()).'">', false);
        }

        $this->withoutVite()->get('/occasions/newborn')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Web/Occasion', false)
            ->has('templates', 1)
            ->where('templates.0.name', 'مسعّر')
            ->where('occasion.label', 'حفل مولود'));

        $this->get('/occasions/unknown')->assertNotFound();
    }

    public function test_the_sitemap_lists_public_pages_and_occasions_but_no_account_pages(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        foreach (['/templates', '/about', '/occasions/project-opening', '/occasions/marriage-contract'] as $path) {
            $response->assertSee(url($path), false);
        }

        $response->assertDontSee('/app', false)->assertDontSee('/admin', false);
    }

    public function test_pages_send_their_title_and_description_in_the_html_the_server_returns(): void
    {
        $this->withoutVite()->get('/templates')
            ->assertSee('<title inertia>مكتبة القوالب — ميثاق</title>', false)
            ->assertSee('property="og:site_name"', false);
    }
}
