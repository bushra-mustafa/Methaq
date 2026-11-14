<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Models\ContactChannel;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use App\Domains\Users\Services\ContactChannels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeleteContactChannelAction
{
    public function __construct(private readonly AuditLogger $auditLogger, private readonly ContactChannels $channels) {}

    public function execute(User $actor, int $channelId): void
    {
        DB::transaction(function () use ($actor, $channelId): void {
            $channel = ContactChannel::query()->lockForUpdate()->findOrFail($channelId);
            Gate::forUser($actor)->authorize('delete', $channel);

            $channel->delete();

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::ContactChannelDeleted,
                subjectType: AuditSubjectType::ContactChannel,
                subjectId: $channelId,
                metadata: ['name' => $channel->name],
            );
        });

        $this->channels->forget();
    }
}
