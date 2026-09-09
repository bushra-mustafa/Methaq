<?php

declare(strict_types=1);

namespace App\Domains\Events\Services;

use App\Domains\Events\DTOs\CreateEventData;
use App\Domains\Events\Enums\EventCategory;
use Illuminate\Support\Str;

final class EventSubdomainGenerator
{
    /** @var list<string> */
    private const RESERVED = [
        'admin', 'api', 'app', 'assets', 'dashboard', 'dev', 'login', 'mail', 'methaq',
        'register', 'smtp', 'staging', 'static', 'status', 'support', 'www',
    ];

    public function generate(CreateEventData $data, EventCategory $category, int $attempt): string
    {
        $base = Str::slug($data->title);
        if ($base === '') {
            $base = str_replace('_', '-', $category->value);
        }
        if (in_array($base, self::RESERVED, true)) {
            $base = 'event-'.$base;
        }

        $year = $data->eventDate->setTimezone($data->timezone)->format('Y');
        $suffix = $attempt === 0 ? '' : '-'.Str::lower(Str::random(6));
        $maximumBaseLength = 63 - strlen($year) - strlen($suffix) - 1;
        $base = trim(substr($base, 0, $maximumBaseLength), '-');

        return $base.'-'.$year.$suffix;
    }
}
