<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class PrototypeRouteAccessTest extends TestCase
{
    public function test_prototype_routes_are_not_available_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->withoutVite()->get('/dev/design-lab')->assertNotFound();
        $this->get('/app/proposals/ivory-wedding')->assertNotFound();
        $this->patchJson('/app/proposals/ivory-wedding', [])->assertNotFound();
    }
}
