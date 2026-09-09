<?php

declare(strict_types=1);

namespace App\Domains\Events\DTOs;

use App\Domains\Events\Enums\EventCategory;
use App\Domains\Events\Enums\EventPresentationState;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;

final readonly class EventData
{
    /**
     * @param  array{id: string, slug: string, name: string, thumbnailUrl: string}|null  $template
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $category,
        public string $categoryLabel,
        public string $subdomain,
        public string $eventDate,
        public string $timezone,
        public string $expiresAt,
        public string $status,
        public bool $isPaid,
        public string $presentationState,
        public ?array $template,
        public int $designRevision,
    ) {}

    public static function fromModel(Event $event): self
    {
        $template = $event->template;
        $design = $event->design;

        return new self(
            id: (string) $event->getKey(),
            title: $event->title,
            category: $event->category->value,
            categoryLabel: self::categoryLabel($event->category),
            subdomain: $event->subdomain,
            eventDate: $event->event_date->setTimezone($event->timezone)->toIso8601String(),
            timezone: $event->timezone,
            expiresAt: $event->expires_at->setTimezone($event->timezone)->toIso8601String(),
            status: $event->status->value,
            isPaid: $event->is_paid,
            presentationState: self::presentationState($event)->value,
            template: $template === null ? null : [
                'id' => (string) $template->getKey(),
                'slug' => $template->slug,
                'name' => $template->name,
                'thumbnailUrl' => '/'.ltrim($template->thumbnail_path, '/'),
            ],
            designRevision: $design?->revision ?? 1,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'categoryLabel' => $this->categoryLabel,
            'subdomain' => $this->subdomain,
            'eventDate' => $this->eventDate,
            'timezone' => $this->timezone,
            'expiresAt' => $this->expiresAt,
            'status' => $this->status,
            'isPaid' => $this->isPaid,
            'presentationState' => $this->presentationState,
            'template' => $this->template,
            'designRevision' => $this->designRevision,
        ];
    }

    private static function categoryLabel(EventCategory $category): string
    {
        return match ($category) {
            EventCategory::Wedding => 'زفاف',
            EventCategory::Henna => 'حنّة',
            EventCategory::MarriageContract => 'عقد قران',
            EventCategory::Graduation => 'تخرج',
        };
    }

    private static function presentationState(Event $event): EventPresentationState
    {
        if ($event->status === EventStatus::Expired || $event->expires_at->isPast()) {
            return EventPresentationState::Expired;
        }

        if ($event->status === EventStatus::Draft) {
            return EventPresentationState::Draft;
        }

        return $event->design?->final_render_path === null
            ? EventPresentationState::Preparing
            : EventPresentationState::Ready;
    }
}
