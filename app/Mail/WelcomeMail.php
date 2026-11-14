<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent once after sign-up: what the account can do and where to start. */
final class WelcomeMail extends Mailable
{
    public function __construct(public readonly string $name) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'أهلاً بك في ميثاق');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.welcome');
    }
}
