<?php

declare(strict_types=1);

namespace App\Domains\Users\Actions;

use App\Domains\Users\Enums\SupportThreadStatus;
use App\Domains\Users\Enums\SupportTopic;
use App\Domains\Users\Exceptions\SupportException;
use App\Domains\Users\Models\SupportMessage;
use App\Domains\Users\Models\SupportThread;
use App\Domains\Users\Models\User;
use App\Domains\Users\Services\SupportMailer;
use Illuminate\Support\Facades\DB;

/** A customer starts a conversation with the team: a problem report or a custom-design request. */
final class OpenSupportThreadAction
{
    public function __construct(private readonly SupportMailer $mailer) {}

    public function execute(User $customer, SupportTopic $topic, string $subject, string $body): SupportThread
    {
        $thread = DB::transaction(function () use ($customer, $topic, $subject, $body): SupportThread {
            $locked = User::query()->lockForUpdate()->findOrFail($customer->getKey());

            if (! $locked->isActive()) {
                throw new SupportException('لا يمكن إرسال رسالة من حساب غير نشط.');
            }

            $open = SupportThread::query()->where('user_id', $locked->getKey())->where('status', SupportThreadStatus::Open->value)->count();
            if ($open >= (int) config('methaq.limits.max_open_support_threads')) {
                throw new SupportException('لديك محادثات مفتوحة كثيرة. انتظري الرد عليها أو أضيفي إلى إحداها بدل فتح محادثة جديدة.');
            }

            $thread = SupportThread::query()->create([
                'user_id' => $locked->getKey(),
                'topic' => $topic,
                'subject' => trim($subject),
                'status' => SupportThreadStatus::Open,
                'admin_unread' => true,
                'customer_unread' => false,
                'last_message_at' => now(),
            ]);
            SupportMessage::query()->create([
                'support_thread_id' => $thread->getKey(),
                'author_id' => $locked->getKey(),
                'from_admin' => false,
                'body' => trim($body),
            ]);

            return $thread;
        });

        $this->mailer->notifyTeam($thread, isReply: false);
        $this->mailer->acknowledgeCustomer($thread->loadMissing('user'));

        return $thread;
    }
}
