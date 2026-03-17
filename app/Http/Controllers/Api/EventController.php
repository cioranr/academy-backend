<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::with(['speakers', 'sessions.items'])
            ->when(! $request->user('sanctum')?->isEventsManager(), fn ($q) => $q->where('status', 'published'))
            ->orderBy('date');

        return response()->json($query->get());
    }

    public function show(string $slug): JsonResponse
    {
        $event = Event::with(['speakers', 'sessions.items', 'directors'])
            ->where('slug', $slug)
            ->orWhere('id', is_numeric($slug) ? (int) $slug : null)
            ->firstOrFail();

        return response()->json(
            $event->append(['google_calendar_url', 'outlook_calendar_url'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'subtitle'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'slug'             => 'nullable|string|unique:events',
            'date'             => 'required|date',
            'time_start'       => 'nullable|date_format:H:i,H:i:s',
            'time_end'         => 'nullable|date_format:H:i,H:i:s',
            'location'         => 'nullable|string|max:255',
            'venue'            => 'nullable|string|max:255',
            'credits'          => 'nullable|integer',
            'credits_label'    => 'nullable|string|max:255',
            'image'            => 'nullable|string|max:255',
            'image_small'      => 'nullable|string|max:255',
            'image_big'        => 'nullable|string|max:255',
            'status'           => 'nullable|in:draft,published,cancelled',
            'max_participants' => 'nullable|integer',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $event = Event::create([...$validated, 'created_by' => $request->user()->id]);

        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'subtitle'         => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'date'             => 'sometimes|date',
            'time_start'       => 'nullable|date_format:H:i,H:i:s',
            'time_end'         => 'nullable|date_format:H:i,H:i:s',
            'location'         => 'nullable|string|max:255',
            'venue'            => 'nullable|string|max:255',
            'credits'          => 'nullable|integer',
            'credits_label'    => 'nullable|string|max:255',
            'image'            => 'nullable|string|max:255',
            'image_small'      => 'nullable|string|max:255',
            'image_big'        => 'nullable|string|max:255',
            'status'           => 'nullable|in:draft,published,cancelled',
            'max_participants' => 'nullable|integer',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'schema_org'       => 'nullable|string',
        ]);

        $event->update($validated);

        return response()->json($event->fresh(['speakers', 'sessions.items']));
    }

    public function uploadImage(Request $request, Event $event): JsonResponse
    {
        $this->authorizeManager($request);

        $request->validate(['image' => 'required|file|image|max:5120']);

        $type  = in_array($request->query('type'), ['small', 'big']) ? $request->query('type') : 'main';
        $field = $type === 'main' ? 'image' : "image_{$type}";

        // Delete old uploaded image if it exists
        if ($event->$field && str_starts_with($event->$field, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $event->$field));
        }

        $path = $request->file('image')->store('events', 'public');
        $url  = '/storage/' . $path;

        $event->update([$field => $url]);

        return response()->json([$field => $url]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        $this->authorizeManager($request);
        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }

    public function ics(string $slug): Response
    {
        $event    = Event::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $dt       = $event->calendarDateTimes();
        $location = implode(', ', array_filter([$event->location, $event->venue]));
        $desc     = str_replace(["\r\n", "\n", "\r"], '\\n', $event->description ?? '');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Monza Ares Academy//RO',
            'BEGIN:VEVENT',
            'UID:event-' . $event->id . '@monza-ares.ro',
            'DTSTART:' . $dt['dtStart'],
            'DTEND:'   . $dt['dtEnd'],
            'SUMMARY:' . $event->title,
            $location ? 'LOCATION:' . $location : null,
            $desc      ? 'DESCRIPTION:' . $desc  : null,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        $ics = implode("\r\n", array_filter($lines));

        return response($ics, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="' . $event->slug . '.ics"',
        ]);
    }

    private function authorizeManager(Request $request): void
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403, 'Unauthorized.');
        }
    }
}
