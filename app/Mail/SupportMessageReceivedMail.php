<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Tells the team a customer wrote. The subject line carries the message title; the text stays in the admin panel. */
final class SupportMessageReceivedMail extends Mailable
{
    public function __construct(public readonly string $title, public readonly bool $isReply, public readonly string $inboxUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->isReply ? 'رد جديد: ' : 'رسالة جديدة: ').$this->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.support-team');
    }
}
