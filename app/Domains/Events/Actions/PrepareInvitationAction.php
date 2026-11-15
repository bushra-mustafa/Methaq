<?php

declare(strict_types=1);

namespace App\Domains\Events\Actions;

use App\Domains\Editor\Models\EventDesign;
use App\Domains\Editor\Services\InvitationPng;
use App\Domains\Events\Enums\EventStatus;
use App\Domains\Events\Models\Event;
use App\Domains\Users\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class PrepareInvitationAction
{
    public function __construct(private readonly InvitationPng $png) {}

    public function execute(User $owner, Event $event, UploadedFile $render, int $revision): string
    {
        Gate::forUser($owner)->authorize('update', $event);
        $path = 'prepared-invitations/'.$event->getKey().'/'.Str::uuid().'.png';
        $bytes = $this->png->normalise($render);
        try {
            DB::transaction(function () use ($owner, $event, $path, $bytes, $revision): void {
                $locked = Event::query()->lockForUpdate()->findOrFail($event->getKey());
                Gate::forUser($owner)->authorize('update', $locked);
                if ($locked->status !== EventStatus::Draft || $locked->suspended_at !== null || $locked->event_date->lessThanOrEqualTo(now())) {
                    throw ValidationException::withMessages(['render' => 'هذه الدعوة غير متاحة للتجهيز.']);
                }
                $design = EventDesign::query()->where('event_id', $locked->getKey())->lockForUpdate()->firstOrFail();
                if ($design->revision !== $revision) {
                    throw ValidationException::withMessages(['expectedRevision' => 'تغيّر التصميم. حدّثي الصفحة لتجهيز أحدث نسخة.']);
                }
                if (! Storage::disk('local')->put($path, $bytes)) {
                    throw new RuntimeException('Unable to prepare invitation.');
                }
                $previous = $design->prepared_render_path;
                $design->forceFill(['prepared_render_path' => $path, 'prepared_revision' => $revision])->save();
                if ($previous) {
                    DB::afterCommit(static fn () => Storage::disk('local')->delete($previous));
                }
            });

            return $path;
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}
