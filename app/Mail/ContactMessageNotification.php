<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mesaj nou de contact – ' . $this->contactMessage->first_name . ' ' . $this->contactMessage->last_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message',
            with: [
                'firstName' => $this->contactMessage->first_name,
                'lastName'  => $this->contactMessage->last_name,
                'email'     => $this->contactMessage->email,
                'phone'     => $this->contactMessage->phone,
                'message'   => $this->contactMessage->message,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
