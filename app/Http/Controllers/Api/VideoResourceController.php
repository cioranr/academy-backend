<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoResourceController extends Controller
{
    /** Admin listing — all resources regardless of status. */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        return response()->json(
            VideoResource::with('doctors')->orderByDesc('updated_at')->get()
        );
    }

    /**
     * Public detail by slug — only if active.
     * Intentionally available without auth, since pages are shared via direct link / QR.
     * Pages must still be excluded from sitemap and marked noindex on the frontend.
     */
    public function show(string $slug): JsonResponse
    {
        $resource = VideoResource::with('doctors')
            ->where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        return response()->json($resource);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:video_resources,slug',
            'short_description' => 'nullable|string|max:500',
            'content'           => 'nullable|string',
            'video_embed'       => 'nullable|string|max:500',
            'active'            => 'nullable|boolean',
            'doctor_ids'        => 'nullable|array',
            'doctor_ids.*'      => 'integer|exists:doctors,id',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $doctorIds = $validated['doctor_ids'] ?? [];
        unset($validated['doctor_ids']);

        $resource = VideoResource::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $this->syncDoctors($resource, $doctorIds);

        return response()->json($resource->fresh('doctors'), 201);
    }

    public function update(Request $request, VideoResource $videoResource): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'title'             => 'sometimes|string|max:255',
            'slug'              => 'sometimes|string|max:255|unique:video_resources,slug,' . $videoResource->id,
            'short_description' => 'nullable|string|max:500',
            'content'           => 'nullable|string',
            'video_embed'       => 'nullable|string|max:500',
            'active'            => 'nullable|boolean',
            'doctor_ids'        => 'nullable|array',
            'doctor_ids.*'      => 'integer|exists:doctors,id',
        ]);

        $doctorIds = array_key_exists('doctor_ids', $validated) ? $validated['doctor_ids'] : null;
        unset($validated['doctor_ids']);

        $videoResource->update($validated);

        if ($doctorIds !== null) {
            $this->syncDoctors($videoResource, $doctorIds);
        }

        return response()->json($videoResource->fresh('doctors'));
    }

    public function uploadVideo(Request $request, VideoResource $videoResource): JsonResponse
    {
        $this->authorizeManager($request);

        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime|max:512000', // 500 MB
        ]);

        if ($videoResource->video_path && str_starts_with($videoResource->video_path, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $videoResource->video_path));
        }

        $path = $request->file('video')->store('video-resources', 'public');
        $url  = '/storage/' . $path;

        $videoResource->update(['video_path' => $url]);

        return response()->json(['video_path' => $url]);
    }

    public function destroy(Request $request, VideoResource $videoResource): JsonResponse
    {
        $this->authorizeManager($request);

        if ($videoResource->video_path && str_starts_with($videoResource->video_path, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $videoResource->video_path));
        }

        $videoResource->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    /** Sync doctors with the given order (array order = pivot order). */
    private function syncDoctors(VideoResource $resource, array $doctorIds): void
    {
        $sync = [];
        foreach (array_values($doctorIds) as $i => $id) {
            $sync[(int) $id] = ['order' => $i];
        }
        $resource->doctors()->sync($sync);
    }

    private function authorizeManager(Request $request): void
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403, 'Unauthorized.');
        }
    }
}
