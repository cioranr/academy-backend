<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\DiplomaMail;
use App\Mail\FeedbackFormMail;
use App\Models\Degree;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\FeedbackResponse;
use App\Support\DiplomaFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    /**
     * Mark a registration as present and optionally send feedback email.
     */
    public function markPresent(Request $request, EventRegistration $registration): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        // Already marked — return current state without resending
        if ($registration->is_present) {
            return response()->json($registration->fresh());
        }

        $registration->update([
            'is_present' => true,
            'present_at' => now(),
        ]);

        $event = $registration->event()->with('questionnaire')->first();

        // Send feedback form email if the event has feedback configured
        if ($event->send_feedback && $event->questionnaire && $registration->user_id) {
            $token = Str::uuid()->toString();

            $registration->update([
                'feedback_token'   => $token,
                'feedback_sent_at' => now(),
            ]);

            try {
                Mail::to($registration->email)->send(new FeedbackFormMail($registration->fresh(), $event));
            } catch (\Throwable) {
                // Don't fail if email sending fails
            }
        }

        return response()->json($registration->fresh());
    }

    /**
     * Public: return the feedback form data for a given token.
     */
    public function showForm(string $token): JsonResponse
    {
        $registration = EventRegistration::where('feedback_token', $token)->firstOrFail();

        if ($registration->feedback_completed) {
            return response()->json(['message' => 'Formularul a fost deja completat.'], 409);
        }

        $event = $registration->event()->with('questionnaire.questions')->firstOrFail();

        if (! $event->questionnaire) {
            abort(404);
        }

        return response()->json([
            'registration' => [
                'first_name' => $registration->first_name,
                'last_name'  => $registration->last_name,
            ],
            'event' => [
                'title'    => $event->title,
                'date'     => $event->date,
                'location' => $event->location,
            ],
            'questionnaire' => $event->questionnaire,
        ]);
    }

    /**
     * Public: submit feedback answers, generate and email diploma.
     */
    public function submitForm(Request $request, string $token): JsonResponse
    {
        $registration = EventRegistration::where('feedback_token', $token)->firstOrFail();

        if ($registration->feedback_completed) {
            return response()->json(['message' => 'Formularul a fost deja completat.'], 409);
        }

        $event = $registration->event()->with('questionnaire.questions', 'directors.doctor')->firstOrFail();

        if (! $event->questionnaire) {
            abort(404);
        }

        $validated = $request->validate([
            'answers'   => 'required|array',
            'answers.*' => 'nullable',
        ]);

        // Save feedback response
        FeedbackResponse::create([
            'registration_id'  => $registration->id,
            'questionnaire_id' => $event->questionnaire->id,
            'answers'          => $validated['answers'],
            'completed_at'     => now(),
        ]);

        $registration->update(['feedback_completed' => true]);

        // Generate diploma
        $degree = $this->generateDiploma($registration, $event);

        // Send diploma email
        if ($degree) {
            try {
                Mail::to($registration->email)->send(new DiplomaMail(
                    registration:    $registration,
                    event:           $event,
                    diplomaPath:     $degree->file_path,
                    diplomaFileName: $degree->file_name,
                ));
                $registration->update(['diploma_sent' => true]);
            } catch (\Throwable) {
                // Don't fail if email sending fails
            }
        }

        return response()->json(['message' => 'Feedback trimis cu succes. Diploma a fost trimisă pe email.']);
    }

    /**
     * Admin: get feedback statistics for an event.
     */
    public function eventStats(Request $request, int $eventId): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $event = Event::with('questionnaire')->findOrFail($eventId);

        $registrations = EventRegistration::where('event_id', $eventId)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->with('feedbackResponse')
            ->get();

        $present          = $registrations->where('is_present', true);
        $feedbackSent     = $registrations->whereNotNull('feedback_sent_at');
        $feedbackDone     = $registrations->where('feedback_completed', true);
        $feedbackPending  = $feedbackSent->where('feedback_completed', false);
        $diplomaSent      = $registrations->where('diploma_sent', true);

        return response()->json([
            'event'              => ['id' => $event->id, 'title' => $event->title, 'send_feedback' => $event->send_feedback, 'questionnaire' => $event->questionnaire],
            'total'              => $registrations->count(),
            'present'            => $present->count(),
            'feedback_sent'      => $feedbackSent->count(),
            'feedback_completed' => $feedbackDone->count(),
            'feedback_pending'   => $feedbackPending->count(),
            'diploma_sent'       => $diplomaSent->count(),
            'participants'       => $registrations->map(fn ($r) => [
                'id'                 => $r->id,
                'first_name'         => $r->first_name,
                'last_name'          => $r->last_name,
                'email'              => $r->email,
                'is_present'         => $r->is_present,
                'present_at'         => $r->present_at,
                'feedback_sent_at'   => $r->feedback_sent_at,
                'feedback_completed' => $r->feedback_completed,
                'diploma_sent'       => $r->diploma_sent,
            ]),
        ]);
    }

    /**
     * Admin: export feedback responses as CSV.
     */
    public function exportFeedback(Request $request, int $eventId): Response
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $event = Event::with('questionnaire.questions')->findOrFail($eventId);

        $responses = FeedbackResponse::where('questionnaire_id', $event->questionnaire?->id)
            ->whereHas('registration', fn ($q) => $q->where('event_id', $eventId))
            ->with('registration')
            ->get();

        $questions = $event->questionnaire?->questions ?? collect();

        $headers = ['Nume', 'Prenume', 'Email', 'Data completare'];
        foreach ($questions as $q) {
            $headers[] = $q->question;
        }

        $rows = [];
        foreach ($responses as $response) {
            $row = [
                $response->registration->last_name,
                $response->registration->first_name,
                $response->registration->email,
                $response->completed_at->format('d.m.Y H:i'),
            ];
            foreach ($questions as $q) {
                $answer = $response->answers[$q->id] ?? '';
                if (is_array($answer)) {
                    $answer = implode(', ', $answer);
                }
                $row[] = $answer;
            }
            $rows[] = $row;
        }

        $csv = 'sep=;' . "\r\n";
        $csv .= implode(';', array_map(fn ($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\r\n";
        foreach ($rows as $row) {
            $csv .= implode(';', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\r\n";
        }

        $filename = 'feedback-' . ($event->slug ?? $eventId) . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->withHeaders(['Content-Encoding' => 'UTF-8'])
          ->setContent("\xEF\xBB\xBF" . $csv); // BOM for Excel UTF-8
    }

    /**
     * Generate a diploma PDF for the given registration and event.
     */
    private function generateDiploma(EventRegistration $registration, Event $event): ?Degree
    {
        if (! $registration->user_id) {
            return null;
        }

        $existing = Degree::where('user_id', $registration->user_id)
            ->where('event_id', $event->id)
            ->first();

        // Don't regenerate if diploma already exists
        if ($existing) {
            return $existing;
        }

        $doctorName = trim($registration->first_name . ' ' . $registration->last_name);
        $location   = trim($event->venue ?? '');

        $start = Carbon::parse($event->date)->locale('ro');
        $end   = $event->end_date ? Carbon::parse($event->end_date)->locale('ro') : null;
        if ($end && $end->gt($start)) {
            $period = $start->isSameMonth($end)
                ? $start->day . '-' . $end->day . ' ' . $start->isoFormat('MMMM YYYY')
                : $start->isoFormat('D MMMM') . ' - ' . $end->isoFormat('D MMMM YYYY');
        } else {
            $period = $start->isoFormat('D MMMM YYYY');
        }

        $logoFile  = $event->credits ? 'logos/amici_logo.png' : 'logos/logo.png';
        $logoAlign = $event->credits ? 'right' : 'left';
        $logoPath  = Storage::disk('public')->exists($logoFile)
            ? storage_path('app/public/' . $logoFile)
            : null;

        $signers = $event->directors->map(function ($d) {
            $sig     = optional($d->doctor)->signature;
            $sigPath = null;
            if ($sig && str_starts_with($sig, '/storage/')) {
                $rel = ltrim(str_replace('/storage/', '', $sig), '/');
                if (Storage::disk('public')->exists($rel)) {
                    $sigPath = storage_path('app/public/' . $rel);
                }
            }
            return ['name' => $d->name, 'specialty' => $d->specialty, 'signature' => $sigPath];
        })->values()->all();

        $fontCache = storage_path('fonts');
        if (! is_dir($fontCache)) {
            @mkdir($fontCache, 0775, true);
        }

        $faces = DiplomaFonts::scanFaces();

        $pdf = Pdf::loadView('pdf.diploma', [
            'doctor_name'    => $doctorName,
            'workshop_title' => $event->title,
            'location'       => $location,
            'period'         => $period,
            'credits'        => $event->credits,
            'cmr_address'    => $event->cmr_address,
            'logo_path'      => $logoPath,
            'logo_align'     => $logoAlign,
            'signers'        => $signers,
            'font_faces'     => $faces,
        ])->setPaper('a4', 'landscape')
          ->setOptions([
              'fontDir'           => $fontCache,
              'fontCache'         => $fontCache,
              'tempDir'           => $fontCache,
              'chroot'            => storage_path('app/public'),
              'isRemoteEnabled'   => true,
          ]);

        $slug      = $event->slug ?: 'event-' . $event->id;
        $fileName  = 'diploma-' . $slug . '-' . $registration->id . '.pdf';
        $storePath = "degrees/{$registration->user_id}/{$fileName}";

        Storage::disk('public')->put($storePath, $pdf->output());

        return Degree::create([
            'user_id'     => $registration->user_id,
            'event_id'    => $event->id,
            'title'       => 'Certificat de participare — ' . $event->title,
            'file_path'   => $storePath,
            'file_name'   => $fileName,
            'uploaded_by' => $registration->user_id,
        ]);
    }
}
