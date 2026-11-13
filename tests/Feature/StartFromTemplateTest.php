<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\Template;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class StartFromTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Integration tests require the isolated MySQL test database.');
        }

        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Refusing to refresh a database without the _test suffix.');
        }
    }

    public function test_a_signed_in_owner_goes_straight_to_creating_from_the_template(): void
    {
        $template = Template::factory()->create(['slug' => 'ivory-sample']);

        $this->actingAs(User::factory()->create())
            ->get('/start/ivory-sample')
            ->assertRedirect(route('app.events.create', ['template' => $template->slug]));
    }

    public function test_a_visitor_signs_up_and_comes_back_to_the_same_template(): void
    {
        Template::factory()->create(['slug' => 'ivory-sample']);

        $this->get('/start/ivory-sample')->assertRedirect(route('register'));

        $this->post('/register', ['name' => 'سارة', 'email' => 'sara@example.com', 'password' => 'Str0ng-Password!', 'password_confirmation' => 'Str0ng-Password!'])
            ->assertRedirect(url('/start/ivory-sample'));
    }

    public function test_hidden_templates_cannot_be_started(): void
    {
        Template::factory()->create(['slug' => 'no-price', 'price_minor' => 0]);
        Template::factory()->create(['slug' => 'switched-off', 'is_active' => false]);

        $this->get('/start/no-price')->assertNotFound();
        $this->get('/start/switched-off')->assertNotFound();
        $this->get('/start/unknown')->assertNotFound();
    }
}
