<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Concerns\VerifiesRecaptcha;
use App\Mail\EventRegistrationConfirmation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EventRegistrationController extends Controller
{
    use VerifiesRecaptcha;
    /**
     * Register (or guest-register) to an event.
     * If the user is authenticated, link the registration to their account.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('status', 'published')->firstOrFail();

        $this->checkBotProtection($request);

        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'specialty'         => 'nullable|string|max:100',
            'professional_grade'=> 'nullable|string|max:100',
            'cuim'              => 'nullable|string|max:30',
            'message'           => 'nullable|string|max:1000',
        ]);

        // Prevent duplicate registration (check by email)
        $alreadyRegistered = EventRegistration::where('event_id', $event->id)
            ->where('email', $validated['email'])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($alreadyRegistered) {
            return response()->json(['message' => 'Ești deja înregistrat la acest eveniment.'], 422);
        }

        if ($event->max_participants) {
            $count = EventRegistration::where('event_id', $event->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->count();
            if ($count >= $event->max_participants) {
                return response()->json(['message' => 'Evenimentul este complet. Nu mai sunt locuri disponibile.'], 422);
            }
        }

        // ── Find or create user account ────────────────────────────────
        $isNewAccount      = false;
        $generatedPassword = null;

        // Use sanctum guard explicitly — resolves Bearer token even on public routes
        $user = $request->user('sanctum')
            ?? User::where('email', $validated['email'])->first();

        if (! $user) {
            $isNewAccount      = true;
            $generatedPassword = Str::password(12, symbols: false);

            $user = User::create([
                'name'              => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'first_name'        => $validated['first_name'],
                'last_name'         => $validated['last_name'],
                'email'             => $validated['email'],
                'password'          => $generatedPassword,
                'phone'             => $validated['phone'] ?? null,
                'specialty'         => $validated['specialty'] ?? null,
                'professional_grade'=> $validated['professional_grade'] ?? null,
                'cuim'              => $validated['cuim'] ?? null,
                'role'              => 'participant',
            ]);
        }

        $registration = EventRegistration::create([
            ...$validated,
            'event_id' => $event->id,
            'user_id'  => $user->id,
            'status'   => 'pending',
        ]);

        // ── Send confirmation email ─────────────────────────────────────
        try {
            Mail::to($validated['email'])->send(new EventRegistrationConfirmation(
                firstName:         $validated['first_name'],
                email:             $validated['email'],
                event:             $event,
                isNewAccount:      $isNewAccount,
                generatedPassword: $generatedPassword,
            ));
        } catch (\Throwable) {
            // Don't fail the registration if email sending fails
        }

        return response()->json($registration, 201);
    }

    /**
     * List registrations for an event (managers only).
     */
    public function index(Request $request, string $slug): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $event = Event::where('slug', $slug)->firstOrFail();

        return response()->json(
            $event->registrations()->with('user')->orderByDesc('registered_at')->get()
        );
    }

    /**
     * Update registration status (managers only).
     */
    public function updateStatus(Request $request, EventRegistration $registration): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,cancelled',
        ]);

        $registration->update($validated);

        return response()->json($registration->fresh());
    }

    /**
     * Cancel own registration (soft — sets status to cancelled).
     */
    public function cancel(Request $request, EventRegistration $registration): JsonResponse
    {
        $user = $request->user();

        if (! $user || ($registration->user_id !== $user->id && ! $user->isEventsManager())) {
            abort(403);
        }

        $registration->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Registration cancelled.']);
    }

    /**
     * Permanently delete a registration (managers only).
     */
    public function forceDelete(Request $request, EventRegistration $registration): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $registration->delete();

        return response()->json(['message' => 'Înregistrarea a fost ștearsă definitiv.']);
    }

    /**
     * My registrations.
     */
    public function myRegistrations(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->registrations()->with('event')->orderByDesc('registered_at')->get()
        );
    }
}
