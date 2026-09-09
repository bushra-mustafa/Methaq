<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Events\DTOs\CreateEventData;
use App\Domains\Events\Enums\EventCategory;
use App\Domains\Events\Services\EventSubdomainGenerator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class EventSubdomainGeneratorTest extends TestCase
{
    public function test_reserved_and_long_names_produce_safe_subdomains(): void
    {
        $generator = new EventSubdomainGenerator;
        $reserved = $this->data('www');
        $long = $this->data(str_repeat('Very Long Wedding Title ', 20));

        $this->assertSame('event-www-2027', $generator->generate($reserved, EventCategory::Wedding, 0));

        $longSlug = $generator->generate($long, EventCategory::Wedding, 1);
        $this->assertLessThanOrEqual(63, strlen($longSlug));
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*-2027-[a-z0-9]{6}$/', $longSlug);
    }

    private function data(string $title): CreateEventData
    {
        return new CreateEventData(
            title: $title,
            category: EventCategory::Wedding,
            templateId: null,
            eventDate: CarbonImmutable::parse('2027-08-20 18:00:00', 'Africa/Tripoli'),
            timezone: 'Africa/Tripoli',
        );
    }
}
