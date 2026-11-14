<?php

declare(strict_types=1);

namespace App\Domains\Users\Services;

use App\Domains\Users\Models\SupportThread;
use App\Mail\SupportAcknowledgementMail;
use App\Mail\SupportMessageReceivedMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Support emails. A mail failure is reported but never breaks the customer's request: the message
 * is already saved and shows in the admin inbox either way.
 */
final class SupportMailer
{
    public function notifyTeam(SupportThread $thread, bool $isReply): void
    {
        $inbox = (string) config('methaq.support.inbox');
        if ($inbox === '') {
            return;
        }

        $this->send(fn () => Mail::to($inbox)->send(new SupportMessageReceivedMail(
            $this->singleLine($thread->subject),
            $isReply,
            url('/admin/support-threads'),
        )));
    }

    public function acknowledgeCustomer(SupportThread $thread): void
    {
        $customer = $thread->user;

        $this->send(fn () => Mail::to($customer->email)->send(new SupportAcknowledgementMail(
            $this->singleLine($customer->name),
            $this->singleLine($thread->subject),
            route('app.support.show', $thread),
        )));
    }

    /** @param callable(): mixed $send */
    private function send(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** Header values must never carry line breaks. */
    private function singleLine(string $value): string
    {
        return trim((string) preg_replace('/[\p{Cc}\p{Zl}\p{Zp}]+/u', ' ', $value));
    }
}
