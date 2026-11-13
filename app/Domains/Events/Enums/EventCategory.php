<?php

declare(strict_types=1);

namespace App\Domains\Events\Enums;

enum EventCategory: string
{
    case Wedding = 'wedding';
    case Henna = 'henna';
    case MarriageContract = 'marriage_contract';
    case Graduation = 'graduation';
    case ProjectOpening = 'project_opening';
    case Newborn = 'newborn';

    public function label(): string
    {
        return match ($this) {
            self::Wedding => 'زفاف',
            self::Henna => 'حنّة',
            self::MarriageContract => 'عقد قران',
            self::Graduation => 'تخرج',
            self::ProjectOpening => 'افتتاح مشروع',
            self::Newborn => 'حفل مولود',
        };
    }

    /** @return list<array{value: string, label: string}> Every category as a select option, in display order. */
    public static function options(): array
    {
        return array_map(
            static fn (self $category): array => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }
}
