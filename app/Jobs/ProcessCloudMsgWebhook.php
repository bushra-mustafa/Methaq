<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Users\Actions\ProcessCloudMsgWebhookAction;
use App\Domains\Users\Models\CloudMsgWebhook;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessCloudMsgWebhook implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $uniqueFor = 300;

    public int $timeout = 60;

    public function __construct(public readonly int $webhookId) {}

    public function uniqueId(): string
    {
        return (string) $this->webhookId;
    }

    public function handle(ProcessCloudMsgWebhookAction $action): void
    {
        try {
            $action->execute($this->webhookId);
        } catch (Throwable) {
            $event = CloudMsgWebhook::query()->findOrFail($this->webhookId);
            if ($event->status === 'pending') {
                Log::warning('CloudMsg command processing failed.', ['webhook_id' => $event->getKey(), 'attempt' => $event->attempts + 1]);
                $event->forceFill(['attempts' => $event->attempts + 1, 'status' => $event->attempts >= 9 ? 'failed' : 'pending', 'next_attempt_at' => now()->addMinutes(2)])->save();
            }
        }
    }
}
