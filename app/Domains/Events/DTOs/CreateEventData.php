<?php

declare(strict_types=1);

namespace App\Domains\Events\DTOs;

use App\Domains\Events\Enums\EventCategory;
use Carbon\CarbonImmutable;

final readonly class CreateEventData
{
    public function __construct(
        public string $title,
        public EventCategory $category,
        public ?int $templateId,
        public CarbonImmutable $eventDate,
        public string $timezone,
    ) {}
}
