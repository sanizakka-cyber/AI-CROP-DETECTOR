<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User         $user,
        public readonly Subscription $subscription,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your MSAS FarmAI Plan Has Expired');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-expired');
    }

    public function attachments(): array
    {
        return [];
    }
}
