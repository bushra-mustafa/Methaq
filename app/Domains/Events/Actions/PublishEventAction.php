<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Enums\TemplateFeature;
use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\DesignDocumentHydrator;
use App\Domains\Editor\Services\InvitationPng;
use App\Domains\Editor\Services\PublishedFeatureSanitizer;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Events\Services\EventPublishEligibility;
use App\Domains\Events\Services\InvitationExpiry;
use App\Domains\Payments\Services\EventEntitlements;
use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use App\Domains\Users\Services\WhatsAppOutbox;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * The owner publishes a paid draft. The browser supplies the rendered card (PNG); the server
 * re-checks everything under a row lock: owner, draft state, approved payment, event date,
 * the saved design revision and the image itself (real PNG, fixed 1080x1920, re-encoded to
 * strip metadata). Features the event may not use are removed from the published scene only.
 */
final class PublishEventAction
{
    public const DISK = 'public';

    public function __construct(
        private readonly EventPublishEligibility $eligibility,
        private readonly EventEntitlements $entitlements,
        private readonly DesignDocumentHydrator $hydrator,
        private readonly PublishedFeatureSanitizer $sanitizer,
        private readonly InvitationExpiry $expiry,
        private readonly AuditLogger $auditLogger,
        private readonly InvitationPng $png,
        private readonly WhatsAppOutbox $outbox,
    ) {}

    public function execute(User $owner, Event $event, ?UploadedFile $render, int $expectedRevision): Event
    {
        Gate::forUser($owner)->authorize('publish', $event);

        $storedPath = null;

        try {
            return DB::transaction(function () use ($owner, $event, $render, $expectedRevision, &$storedPath): Event {
                $lockedEvent = Event::query()->with('template')->lockForUpdate()->findOrFail($event->getKey());
                Gate::forUser($owner)->authorize('publish', $lockedEvent);
                if ($render === null) {
                    $currentOwner = User::query()->lockForUpdate()->findOrFail($owner->getKey());
                    Gate::forUser($currentOwner)->authorize('publish', $lockedEvent);
                    if (! $this->outbox->verified($currentOwner) || $currentOwner->whatsapp_phone !== $owner->whatsapp_phone) {
                        throw ValidationException::withMessages(['publish' => 'أكدي رقم واتساب من الموقع قبل النشر.']);
                    }
                }

                $order = $this->entitlements->completedOrder($lockedEvent);
                $blocker = $this->eligibility->blocker($lockedEvent, $order);
                if ($blocker !== null) {
                    throw ValidationException::withMessages(['publish' => $blocker]);
                }

                $design = EventDesign::query()->where('event_id', $lockedEvent->getKey())->lockForUpdate()->first();
                if (! $design instanceof EventDesign) {
                    throw new RuntimeException('The event design is unavailable.');
                }
                if ($design->revision !== $expectedRevision) {
                    throw ValidationException::withMessages(['publish' => 'تغيّر التصميم منذ تحميل الصفحة. حدّثي الصفحة ثم انشري مرة أخرى.']);
                }

                try {
                    $document = $this->hydrator->hydrate([
                        'schemaVersion' => $design->schema_version,
                        'canvas' => $design->design_json,
                        'palette' => $design->palette_json,
                        'scene' => $design->scene_json,
                    ]);
                } catch (InvalidArgumentException) {
                    throw ValidationException::withMessages(['publish' => 'التصميم يحتاج مراجعة قبل النشر.']);
                }

                $lockedFeatures = $this->entitlements->locked($lockedEvent, $order);
                ['scene' => $scene, 'removed' => $removed] = $this->sanitizer->sanitize($document->scene, $lockedFeatures);

                $relativePath = 'renders/invitations/'.$design->event_id.'/'.Str::uuid()->toString().'.png';
                $bytes = $render !== null ? $this->png->normalise($render) : $this->preparedBytes($design);
                if (! Storage::disk(self::DISK)->put($relativePath, $bytes)) {
                    throw new RuntimeException('Unable to store the published invitation.');
                }
                $storedPath = $relativePath;

                $now = now();
                $design->forceFill([
                    'published_revision' => $design->revision,
                    'published_scene_json' => $scene->toArray(),
                    'published_palette_json' => $design->palette_json,
                    'final_render_path' => 'storage/'.$relativePath,
                    'rendered_at' => $now,
                ])->save();

                $published = Event::query()->findOrFail($event->getKey());
                $published->forceFill([
                    'status' => EventStatus::Published,
                    'published_at' => $now,
                    'expires_at' => $this->expiry->expiresAt(CarbonImmutable::instance($published->event_date)),
                ])->save();

                $this->auditLogger->record(
                    actor: $owner,
                    action: AuditAction::EventPublished,
                    subjectType: AuditSubjectType::Event,
                    subjectId: (int) $published->getKey(),
                    metadata: [
                        'revision' => $design->revision,
                        'removedFeatures' => array_map(static fn (TemplateFeature $feature): string => $feature->value, $removed),
                        'lockedFeatures' => array_map(static fn (TemplateFeature $feature): string => $feature->value, $lockedFeatures),
                    ],
                );

                $this->outbox->published($published);

                return $published;
            }, 3);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk(self::DISK)->delete($storedPath);
            }

            throw $exception;
        }
    }

    private function preparedBytes(EventDesign $design): string
    {
        if ($design->prepared_revision !== $design->revision || ! $design->prepared_render_path || ! Storage::disk('local')->exists($design->prepared_render_path)) {
            throw ValidationException::withMessages(['publish' => 'تغيّر التصميم أو لم تجهزي النسخة بعد. افتحي صفحة الدفع وجهّزي الدعوة من جديد، دون إعادة الدفع.']);
        }

        return Storage::disk('local')->get($design->prepared_render_path);
    }
}
