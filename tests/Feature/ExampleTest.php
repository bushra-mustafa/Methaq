<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_returns_the_react_inertia_page(): void
    {
        $this->withoutVite()->get('/')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->component('Web/Home', false)->missing('auth')->missing('database')
        );
    }

    public function test_inertia_navigation_returns_the_page_protocol(): void
    {
        $this->withoutVite()->get('/', ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')) ?? ''])
            ->assertOk()->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'Web/Home');
    }
}
