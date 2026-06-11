<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EventRegistration $registration,
        public readonly Event $event,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Formular de feedback – ' . $this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feedback-form',
            with: [
                'firstName'   => $this->registration->first_name,
                'event'       => $this->event,
                'feedbackUrl' => config('app.frontend_url') . '/feedback/' . $this->registration->feedback_token,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
