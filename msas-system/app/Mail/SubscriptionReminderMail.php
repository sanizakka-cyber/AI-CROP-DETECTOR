<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User         $user,
        public readonly Subscription $subscription,
        public readonly int          $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $days = $this->daysLeft === 1 ? 'Tomorrow' : "In {$this->daysLeft} Days";
        return new Envelope(subject: "Your MSAS FarmAI Plan Expires {$days}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-reminder');
    }

    public function attachments(): array
    {
        return [];
    }
}
