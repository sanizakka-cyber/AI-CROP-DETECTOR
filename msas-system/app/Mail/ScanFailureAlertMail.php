<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "N scans failed in 10 minutes": engine, Claude API or internal failures (ScanErrorReporter). */
class ScanFailureAlertMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly string $body, public readonly int $count, public readonly int $windowMinutes)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "MSAS FarmAI alert: {$this->count} scans failed in {$this->windowMinutes} minutes");
    }

    public function content(): Content
    {
        return new Content(htmlString: nl2br(e($this->body)));
    }
}
