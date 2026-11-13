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

        foreach (['/templates', '/about', '/privacy', '/terms', '/occasions/project-opening', '/occasions/marriage-contract'] as $path) {
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

    public function test_robots_closes_private_areas_and_the_whole_of_every_invitation_address(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /app', false)
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);

        $this->get('http://sara-and-omar.'.config('methaq.invitation_domain').'/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /', false)
            ->assertDontSee('Sitemap:', false);
    }

    public function test_only_public_pages_are_indexable_and_all_others_say_noindex(): void
    {
        $this->withoutVite()->get('/login')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->withoutVite()->get('/register')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->withoutVite()->get('/app')->assertRedirect('/login');

        foreach (['/', '/about', '/contact', '/privacy', '/terms', '/templates', '/occasions/wedding'] as $path) {
            $this->withoutVite()->get($path)->assertOk()->assertDontSee('<meta name="robots"', false);
        }
    }

    public function test_every_page_shares_an_absolute_official_brand_image_unless_it_has_its_own(): void
    {
        $this->withoutVite()->get('/about')
            ->assertSee('<meta property="og:image" content="'.url('/brand/images/og-default.png').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);

        [$width, $height] = getimagesize(public_path('brand/images/og-default.png'));
        $this->assertSame([1200, 630], [$width, $height]);
    }

    public function test_favicons_are_linked_in_every_size_the_files_exist_for(): void
    {
        $html = $this->withoutVite()->get('/about')->getContent();

        foreach (['favicon.svg', 'favicon-32.png', 'favicon-16.png', 'favicon.ico', 'apple-touch-icon.png'] as $icon) {
            $this->assertStringContainsString('brand/icons/'.$icon, $html);
            $this->assertFileExists(public_path('brand/icons/'.$icon));
        }
    }
}
