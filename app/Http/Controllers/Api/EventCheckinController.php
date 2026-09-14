<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventCheckinController extends Controller
{
    // ── Admin: QR code management ────────────────────────────────────────────

    /**
     * Current check-in QR data for an event (token + URL), or nulls if not generated yet.
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        $this->authorizeManager($request);

        return response()->json($this->qrPayload($event));
    }

    /**
     * Generate (or regenerate) the check-in token. Regenerating invalidates the previous QR code.
     */
    public function generate(Request $request, Event $event): JsonResponse
    {
        $this->authorizeManager($request);

        $event->forceFill([
            'checkin_token'              => Str::random(40),
            'checkin_token_generated_at' => now(),
        ])->save();

        return response()->json($this->qrPayload($event->fresh()));
    }

    // ── Participant: scan → check-in ─────────────────────────────────────────

    /**
     * Confirm presence for the authenticated user at the event identified by the QR token.
     *
     * Response `status` values: confirmed | already_confirmed
     * Error `code` values (with HTTP status): event_inactive (410), not_registered (404)
     */
    public function checkin(Request $request, string $token, PresenceService $presence): JsonResponse
    {
        $event = Event::where('checkin_token', $token)->first();

        if (! $event || $event->status !== 'published') {
            return response()->json(['code' => 'event_inactive', 'message' => 'Evenimentul nu este activ.'], 410);
        }

        $user = $request->user();

        $registration = EventRegistration::where('event_id', $event->id)
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', $user->email))
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->orderByRaw('user_id = ? desc', [$user->id])
            ->first();

        if (! $registration) {
            return response()->json([
                'code'    => 'not_registered',
                'message' => 'Nu ești înregistrat la acest eveniment.',
                'event'   => $this->eventSummary($event),
            ], 404);
        }

        $alreadyConfirmed = (bool) $registration->is_present;

        if (! $alreadyConfirmed) {
            $registration = $presence->confirm($registration, PresenceService::METHOD_QR, $user);
        }

        return response()->json([
            'status'       => $alreadyConfirmed ? 'already_confirmed' : 'confirmed',
            'message'      => $alreadyConfirmed ? 'Prezența a fost deja confirmată.' : 'Prezența ta a fost confirmată cu succes.',
            'participant'  => [
                'first_name' => $registration->first_name,
                'last_name'  => $registration->last_name,
            ],
            'present_at'   => $registration->present_at,
            'event'        => $this->eventSummary($event),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeManager(Request $request): void
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403, 'Unauthorized.');
        }
    }

    private function qrPayload(Event $event): array
    {
        return [
            'token'        => $event->checkin_token,
            'url'          => $event->checkinUrl(),
            'generated_at' => $event->checkin_token_generated_at,
        ];
    }

    private function eventSummary(Event $event): array
    {
        return [
            'id'         => $event->id,
            'slug'       => $event->slug,
            'title'      => $event->title,
            'date'       => $event->date,
            'end_date'   => $event->end_date,
            'time_start' => $event->time_start,
            'time_end'   => $event->time_end,
            'location'   => $event->location,
            'venue'      => $event->venue,
        ];
    }
}
