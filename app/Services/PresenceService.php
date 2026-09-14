<?php

namespace App\Services;

use App\Mail\FeedbackFormMail;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PresenceService
{
    public const METHOD_MANUAL = 'manual';
    public const METHOD_QR     = 'qr';

    /**
     * Mark a registration as present (idempotent) and send the feedback
     * form email if the event has feedback configured.
     */
    public function confirm(EventRegistration $registration, string $method, ?User $confirmedBy = null): EventRegistration
    {
        if ($registration->is_present) {
            return $registration->fresh();
        }

        $registration->update([
            'is_present'            => true,
            'present_at'            => now(),
            'presence_method'       => $method,
            'presence_confirmed_by' => $confirmedBy?->id,
        ]);

        $event = $registration->event()->with('questionnaire')->first();

        if ($event->send_feedback && $event->questionnaire && $registration->user_id && ! $registration->feedback_sent_at) {
            $registration->update([
                'feedback_token'   => Str::uuid()->toString(),
                'feedback_sent_at' => now(),
            ]);

            try {
                Mail::to($registration->email)->send(new FeedbackFormMail($registration->fresh(), $event));
            } catch (\Throwable) {
                // Don't fail if email sending fails
            }
        }

        return $registration->fresh();
    }

    /**
     * Revoke a presence confirmation (admin correction). Feedback/diploma
     * state is left untouched.
     */
    public function revoke(EventRegistration $registration): EventRegistration
    {
        $registration->update([
            'is_present'            => false,
            'present_at'            => null,
            'presence_method'       => null,
            'presence_confirmed_by' => null,
        ]);

        return $registration->fresh();
    }
}
