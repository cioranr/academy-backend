<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSpeaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventSpeakerController extends Controller
{
    public function store(Request $request, string $slug): JsonResponse
    {
        $this->authorizeManager($request);

        $event = Event::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'doctor_id'    => 'nullable|exists:doctors,id',
            'user_id'      => 'nullable|exists:users,id',
            'name'         => 'required|string|max:255',
            'specialty'    => 'nullable|string|max:255',
            'image'        => 'nullable|string|max:255',
            'slug'         => 'nullable|string|max:255',
            'speaker_role' => 'nullable|in:speaker,director',
            'order'        => 'nullable|integer',
        ]);

        $speaker = $event->speakers()->create($validated);

        return response()->json($speaker, 201);
    }

    public function update(Request $request, EventSpeaker $speaker): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'doctor_id'    => 'nullable|exists:doctors,id',
            'name'         => 'sometimes|string|max:255',
            'specialty'    => 'nullable|string|max:255',
            'image'        => 'nullable|string|max:255',
            'slug'         => 'nullable|string|max:255',
            'speaker_role' => 'nullable|in:speaker,director',
            'order'        => 'nullable|integer',
        ]);

        $speaker->update($validated);

        return response()->json($speaker->fresh());
    }

    public function uploadImage(Request $request, EventSpeaker $speaker): JsonResponse
    {
        $this->authorizeManager($request);

        $request->validate(['image' => 'required|file|image|max:5120']);

        if ($speaker->image && str_starts_with($speaker->image, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $speaker->image));
        }

        $path = $request->file('image')->store('speakers', 'public');
        $url  = '/storage/' . $path;

        $speaker->update(['image' => $url]);

        return response()->json(['image' => $url]);
    }

    public function destroy(Request $request, EventSpeaker $speaker): JsonResponse
    {
        $this->authorizeManager($request);
        $speaker->delete();

        return response()->json(['message' => 'Speaker removed.']);
    }

    private function authorizeManager(Request $request): void
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }
    }
}
