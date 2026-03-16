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
                'firstName'          => $this->firstName,
                'email'              => $this->email,
                'event'              => $this->event,
                'isNewAccount'       => $this->isNewAccount,
                'generatedPassword'  => $this->generatedPassword,
                'loginUrl'           => config('app.frontend_url') . '/login',
                'dashboardUrl'       => config('app.frontend_url') . '/dashboard',
                'googleCalendarUrl'  => $this->buildGoogleCalendarUrl(),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $this->buildICS(),
                $this->event->slug . '.ics'
            )->withMime('text/calendar'),
        ];
    }

    private function buildICS(): string
    {
        $date      = \Carbon\Carbon::parse($this->event->date);
        $startTime = $this->event->time_start
            ? str_replace(':', '', substr($this->event->time_start, 0, 5)) . '00'
            : '090000';
        $endTime   = $this->event->time_end
            ? str_replace(':', '', substr($this->event->time_end, 0, 5)) . '00'
            : $startTime;

        $dtStart  = $date->format('Ymd') . 'T' . $startTime;
        $dtEnd    = $date->format('Ymd') . 'T' . $endTime;
        $location = implode(', ', array_filter([$this->event->location, $this->event->venue]));
        $desc     = str_replace(["\r\n", "\n", "\r"], '\\n', $this->event->description ?? '');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Monza Ares Academy//RO',
            'BEGIN:VEVENT',
            'UID:event-' . $this->event->id . '@monza-ares.ro',
            'DTSTART:' . $dtStart,
            'DTEND:'   . $dtEnd,
            'SUMMARY:' . $this->event->title,
            $location ? 'LOCATION:' . $location : null,
            $desc      ? 'DESCRIPTION:' . $desc : null,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_filter($lines));
    }

    private function buildGoogleCalendarUrl(): string
    {
        $date      = \Carbon\Carbon::parse($this->event->date);
        $startTime = $this->event->time_start
            ? str_replace(':', '', substr($this->event->time_start, 0, 5)) . '00'
            : '090000';
        $endTime   = $this->event->time_end
            ? str_replace(':', '', substr($this->event->time_end, 0, 5)) . '00'
            : $startTime;

        $dtStart  = $date->format('Ymd') . 'T' . $startTime;
        $dtEnd    = $date->format('Ymd') . 'T' . $endTime;
        $location = implode(', ', array_filter([$this->event->location, $this->event->venue]));

        return 'https://calendar.google.com/calendar/render?' . http_build_query(array_filter([
            'action'   => 'TEMPLATE',
            'text'     => $this->event->title,
            'dates'    => $dtStart . '/' . $dtEnd,
            'location' => $location,
            'details'  => $this->event->description ?? '',
        ]));
    }
}
