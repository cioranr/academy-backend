<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $firstName,
        public readonly string  $email,
        public readonly Event   $event,
        public readonly bool    $isNewAccount,
        public readonly ?string $generatedPassword = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmare înregistrare – ' . $this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event-registration-confirmation',
            with: [
                'firstName'           => $this->firstName,
                'email'               => $this->email,
                'event'               => $this->event,
                'isNewAccount'        => $this->isNewAccount,
                'generatedPassword'   => $this->generatedPassword,
                'loginUrl'            => config('app.frontend_url') . '/login',
                'dashboardUrl'        => config('app.frontend_url') . '/dashboard',
                'googleCalendarUrl'   => $this->event->google_calendar_url,
                'outlookCalendarUrl'  => $this->event->outlook_calendar_url,
                'icsUrl'              => config('app.url') . '/api/events/' . $this->event->slug . '/ics',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
