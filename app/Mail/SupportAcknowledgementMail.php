<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Automatic reply to the customer: her message arrived and the team will answer once they read it. */
final class SupportAcknowledgementMail extends Mailable
{
    public function __construct(public readonly string $name, public readonly string $title, public readonly string $threadUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'وصلتنا رسالتك | ميثاق');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.support-ack');
    }
}
