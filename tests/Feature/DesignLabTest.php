<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DesignLabTest extends TestCase
{
    public function test_prototype_is_available_locally(): void
    {
        $this->withoutVite()->get('/dev/design-lab')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->component('App/DesignLab', false)
        );
    }

    public function test_prototype_is_not_available_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->withoutVite()->get('/dev/design-lab')->assertNotFound();
    }
}
