<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User   $staff,
        public readonly string $temporaryPassword,
        public readonly bool   $isReset = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isReset
            ? 'Your MSAS FarmAI Password Has Been Reset'
            : 'Welcome to MSAS FarmAI — Your Account is Ready';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.staff-welcome');
    }

    public function attachments(): array
    {
        return [];
    }
}
