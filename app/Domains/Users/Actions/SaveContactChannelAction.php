<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\AuditAction;
use App\Domains\Users\Enums\AuditSubjectType;
use App\Domains\Users\Enums\ContactIcon;
use App\Domains\Users\Models\ContactChannel;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\AuditLogger;
use App\Domains\Users\Services\ContactChannels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** An administrator adds or edits a contact channel. The link must be https, mailto or tel. */
final class SaveContactChannelAction
{
    public function __construct(private readonly AuditLogger $auditLogger, private readonly ContactChannels $channels) {}

    /** @param array{name: string, url: string, icon: ContactIcon|string, is_active: bool, sort_order: int} $data */
    public function execute(User $actor, array $data, ?int $channelId = null): ContactChannel
    {
        $url = trim($data['url']);
        if (preg_match(ContactChannel::URL_PATTERN, $url) !== 1) {
            throw ValidationException::withMessages(['url' => 'الرابط يجب أن يبدأ بـ https:// أو mailto: أو tel:.']);
        }

        $channel = DB::transaction(function () use ($actor, $data, $url, $channelId): ContactChannel {
            $channel = $channelId === null ? new ContactChannel : ContactChannel::query()->lockForUpdate()->findOrFail($channelId);
            Gate::forUser($actor)->authorize($channelId === null ? 'create' : 'update', $channelId === null ? ContactChannel::class : $channel);

            $channel->fill([
                'name' => trim($data['name']),
                'url' => $url,
                'icon' => $data['icon'] instanceof ContactIcon ? $data['icon'] : ContactIcon::from($data['icon']),
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'],
            ])->save();

            $this->auditLogger->record(
                actor: $actor,
                action: AuditAction::ContactChannelSaved,
                subjectType: AuditSubjectType::ContactChannel,
                subjectId: (int) $channel->getKey(),
                metadata: ['name' => $channel->name, 'active' => $channel->is_active],
            );

            return $channel;
        });

        $this->channels->forget();

        return $channel;
    }
}
