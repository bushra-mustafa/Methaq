<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Enums\UserRole;
use App\Domains\Users\Exceptions\SupportException;
use App\Domains\Users\Models\SupportMessage;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\SupportMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Adds a message to a conversation: by its owner or by an administrator. A closed conversation takes no replies. */
final class ReplyToSupportThreadAction
{
    private const MAX_MESSAGES_PER_THREAD = 200;

    public function __construct(private readonly SupportMailer $mailer) {}

    public function execute(User $actor, int $threadId, string $body): SupportMessage
    {
        $alreadyNotified = false;
        [$message, $thread] = DB::transaction(function () use ($actor, $threadId, $body, &$alreadyNotified): array {
            $thread = SupportThread::query()->lockForUpdate()->findOrFail($threadId);
            Gate::forUser($actor)->authorize('reply', $thread);

            if ($thread->status === SupportThreadStatus::Closed) {
                throw new SupportException('هذه المحادثة مغلقة. افتحي محادثة جديدة إن احتجتِ شيئاً آخر.');
            }

            if ($thread->messages()->count() >= self::MAX_MESSAGES_PER_THREAD) {
                throw new SupportException('بلغت المحادثة حدّها الأقصى من الرسائل.');
            }

            $fromAdmin = $actor->role === UserRole::Admin && $thread->user_id !== $actor->getKey();
            // The team is already told while the customer's earlier message is still unread.
            $alreadyNotified = $thread->admin_unread;

            $message = SupportMessage::query()->create([
                'support_thread_id' => $thread->getKey(),
                'author_id' => $actor->getKey(),
                'from_admin' => $fromAdmin,
                'body' => trim($body),
            ]);

            $thread->forceFill([
                'last_message_at' => now(),
                'admin_unread' => ! $fromAdmin,
                'customer_unread' => $fromAdmin,
            ])->save();

            return [$message, $thread];
        });

        if (! $message->from_admin && ! $alreadyNotified) {
            $this->mailer->notifyTeam($thread, isReply: true);
        }

        return $message;
    }
}
