<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDeviceLoginMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $user,
        public string $browser,
        public string $platform,
        public string $ipAddress,
        public string $loginTime,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New sign-in to your MSAS FarmAI account');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-device-login');
    }
}
