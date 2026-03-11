<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\EventSessionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventSessionController extends Controller {
    public function store(Request $request, Event $event): JsonResponse {
        if (!$request->user()?->isEventsManager()) abort(403);
        $validated = $request->validate(['time_label' => 'required|string', 'title' => 'required|string', 'order' => 'nullable|integer']);
        $session = $event->sessions()->create($validated);
        return response()->json($session->load('items'), 201);
    }

    public function update(Request $request, EventSession $session): JsonResponse {
        if (!$request->user()?->isEventsManager()) abort(403);
        $session->update($request->validate(['time_label' => 'sometimes|string', 'title' => 'sometimes|string', 'order' => 'nullable|integer']));
        return response()->json($session->fresh('items'));
    }

    public function destroy(Request $request, EventSession $session): JsonResponse {
        if (!$request->user()?->isEventsManager()) abort(403);
        $session->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function storeItem(Request $request, EventSession $session): JsonResponse {
        if (!$request->user()?->isEventsManager()) abort(403);
        $item = $session->items()->create($request->validate(['content' => 'required|string', 'order' => 'nullable|integer']));
        return response()->json($item, 201);
    }

    public function destroyItem(Request $request, EventSessionItem $item): JsonResponse {
        if (!$request->user()?->isEventsManager()) abort(403);
        $item->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
