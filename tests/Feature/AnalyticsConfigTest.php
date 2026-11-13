<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class AnalyticsConfigTest extends TestCase
{
    public function test_analytics_are_off_in_local_and_test_environments_even_if_a_domain_is_present(): void
    {
        config(['methaq.analytics.domain' => 'methq.ly']);

        $this->assertFalse((bool) config('methaq.analytics.enabled'), 'The flag needs ANALYTICS_ENABLED and production.');

        $this->withoutVite()->get('/about')->assertInertia(fn (AssertableInertia $page) => $page->where('analytics.enabled', false));
    }

    public function test_analytics_switch_on_only_with_both_the_flag_and_a_domain(): void
    {
        config(['methaq.analytics.enabled' => true, 'methaq.analytics.domain' => null]);
        $this->withoutVite()->get('/about')->assertInertia(fn (AssertableInertia $page) => $page->where('analytics.enabled', false));

        config(['methaq.analytics.enabled' => true, 'methaq.analytics.domain' => 'methq.ly']);
        $this->withoutVite()->get('/about')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('analytics.enabled', true)
            ->where('analytics.domain', 'methq.ly'));
    }

    public function test_private_areas_are_excluded_from_the_counter_by_configuration(): void
    {
        $exclude = (string) config('methaq.analytics.exclude');

        foreach (['/app/*', '/admin/*', '/login', '/register', '/reset-password/*', '/start/*'] as $path) {
            $this->assertStringContainsString($path, $exclude);
        }
    }

    public function test_the_shared_analytics_block_never_carries_personal_data(): void
    {
        config(['methaq.analytics.enabled' => true, 'methaq.analytics.domain' => 'methq.ly']);

        $this->withoutVite()->get('/about')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('analytics', fn ($analytics): bool => array_keys($analytics->toArray()) === ['enabled', 'domain', 'script', 'exclude']));
    }
}
