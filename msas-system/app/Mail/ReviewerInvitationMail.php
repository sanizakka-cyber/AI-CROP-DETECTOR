<?php

namespace App\Mail;

use App\Models\SpeechLanguage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation to review "Listen to your result" in one language. Carries a
 * signed first-login link (the same mechanism as StaffWelcomeMail), so the
 * reviewer chooses their own password. No password is ever in the email.
 */
class ReviewerInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $reviewer,
        public readonly SpeechLanguage $language,
        public readonly string $kindLabel,
        public readonly ?string $setPasswordLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "MSAS FarmAI: please help review {$this->language->name_english} results");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reviewer-invitation');
    }
}
