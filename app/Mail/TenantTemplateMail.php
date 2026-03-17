<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantTemplateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $htmlBody;
    public string $textBody;

    public function __construct(
        private readonly array  $rendered,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
        $this->htmlBody = $rendered['body_html'];
        $this->textBody = $rendered['body_text'];
        $this->queue    = 'notifications';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from:    new \Illuminate\Mail\Mailables\Address($this->fromEmail, $this->fromName),
            subject: $this->rendered['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
            textString: $this->textBody,
        );
    }
}
