<?php

declare(strict_types=1);

namespace App\Domains\Users\Listeners;

use App\Domains\Users\Models\User;
use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** A failed welcome email is reported but never blocks sign-up. */
final class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        try {
            Mail::to($user->email)->send(new WelcomeMail(trim((string) preg_replace('/[\p{Cc}\p{Zl}\p{Zp}]+/u', ' ', $user->name))));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
