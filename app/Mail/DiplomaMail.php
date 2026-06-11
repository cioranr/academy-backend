<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class DiplomaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly EventRegistration $registration,
        public readonly Event $event,
        public readonly string $diplomaPath,
        public readonly string $diplomaFileName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Diploma ta – ' . $this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.diploma',
            with: [
                'firstName'    => $this->registration->first_name,
                'event'        => $this->event,
                'dashboardUrl' => config('app.frontend_url') . '/dashboard',
            ],
        );
    }

    public function attachments(): array
    {
        $fullPath = Storage::disk('public')->path($this->diplomaPath);

        if (! file_exists($fullPath)) {
            return [];
        }

        return [
            Attachment::fromPath($fullPath)
                ->as($this->diplomaFileName)
                ->withMime('application/pdf'),
        ];
    }
}
