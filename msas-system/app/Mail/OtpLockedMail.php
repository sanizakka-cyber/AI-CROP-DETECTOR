<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpLockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $ipAddress,
        public string $lockedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verification Blocked — Too Many Attempts');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp-locked');
    }
}
