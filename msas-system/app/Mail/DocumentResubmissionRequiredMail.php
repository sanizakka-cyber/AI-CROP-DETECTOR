<?php

namespace App\Mail;

use App\Models\UserDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentResubmissionRequiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly UserDocument $document,
        public readonly string $resubmitUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action needed: ' . $this->document->document_label . ' — MSAS FarmAI',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.document-resubmission-required',
        );
    }
}
