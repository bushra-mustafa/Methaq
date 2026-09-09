<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Models\Template;
use App\Domains\Editor\Services\BlankDesignFactory;
use App\Domains\Events\DTOs\CreateEventData;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\EventSubdomainGenerator;
use App\Domains\Users\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class CreateEventAction
{
    private const MAXIMUM_SLUG_ATTEMPTS = 5;

    public function __construct(
        private readonly EventSubdomainGenerator $subdomains,
        private readonly BlankDesignFactory $blankDesignFactory,
    ) {}

    public function execute(User $owner, CreateEventData $data): Event
    {
        Gate::forUser($owner)->authorize('create', Event::class);

        for ($attempt = 0; $attempt < self::MAXIMUM_SLUG_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(function () use ($owner, $data, $attempt): Event {
                    $template = $this->activeTemplate($data->templateId);
                    $document = $template === null
                        ? $this->blankDesignFactory->make()->toStorageColumns()
                        : [
                            'design_json' => $template->default_design_json,
                            'scene_json' => $template->default_scene_json,
                            'palette_json' => $template->default_palette_json,
                            'schema_version' => $template->schema_version,
                        ];

                    $eventDate = $data->eventDate->setTimezone('UTC');
                    $expiresAt = $data->eventDate->setTimezone($data->timezone)->addDays(10)->setTimezone('UTC');
                    $category = $template?->category ?? $data->category;
                    $event = new Event;
                    $event->forceFill([
                        'user_id' => $owner->getKey(),
                        'template_id' => $template?->getKey(),
                        'category' => $category,
                        'title' => $data->title,
                        'subdomain' => $this->subdomains->generate($data, $category, $attempt),
                        'event_date' => $eventDate,
                        'timezone' => $data->timezone,
                        'expires_at' => $expiresAt,
                        'status' => EventStatus::Draft,
                        'is_paid' => false,
                    ])->save();

                    $design = new EventDesign;
                    $design->forceFill([
                        'event_id' => $event->getKey(),
                        ...$document,
                        'revision' => 1,
                    ])->save();

                    return $event->setRelation('template', $template)->setRelation('design', $design);
                });
            } catch (QueryException $exception) {
                if (! $this->isSubdomainCollision($exception)) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to reserve a unique event subdomain.');
    }

    private function activeTemplate(?int $templateId): ?Template
    {
        if ($templateId === null) {
            return null;
        }

        $template = Template::query()
            ->whereKey($templateId)
            ->where('is_active', true)
            ->sharedLock()
            ->first();

        if ($template === null) {
            throw ValidationException::withMessages([
                'template_id' => 'القالب المحدد لم يعد متاحاً. اختاري قالباً آخر.',
            ]);
        }

        return $template;
    }

    private function isSubdomainCollision(QueryException $exception): bool
    {
        return ($exception->errorInfo[1] ?? null) === 1062
            && str_contains($exception->getMessage(), 'events_subdomain_unique');
    }
}
