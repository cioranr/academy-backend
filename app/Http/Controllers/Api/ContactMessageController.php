<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageNotification;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:30',
            'message'    => 'nullable|string|max:2000',
        ]);

        $contact = ContactMessage::create($validated);

        try {
            Mail::to('radu@elevateweb.ro')->send(new ContactMessageNotification($contact));
        } catch (\Throwable) {
            // Don't fail the submission if email sending fails
        }

        return response()->json(['message' => 'Mesajul tău a fost trimis cu succes!'], 201);
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isEventsManager()) {
            abort(403);
        }

        return response()->json(
            ContactMessage::orderByDesc('created_at')->get()
        );
    }

    public function markRead(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $contactMessage->update(['read' => true]);

        return response()->json($contactMessage);
    }

    public function destroy(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $contactMessage->delete();

        return response()->json(['message' => 'Mesajul a fost șters.']);
    }
}
