<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Testimonial::orderBy('order')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'title'          => 'nullable|string|max:255',
            'subtitle'       => 'nullable|string',
            'doctor_name'    => 'required|string|max:255',
            'quote'          => 'required|string',
            'workshop_title' => 'nullable|string|max:255',
            'workshop_href'  => 'nullable|string|max:255',
            'active'         => 'nullable|boolean',
            'order'          => 'nullable|integer',
        ]);

        return response()->json(Testimonial::create($validated), 201);
    }

    public function update(Request $request, Testimonial $testimonial): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'title'          => 'nullable|string|max:255',
            'subtitle'       => 'nullable|string',
            'doctor_name'    => 'sometimes|string|max:255',
            'quote'          => 'sometimes|string',
            'workshop_title' => 'nullable|string|max:255',
            'workshop_href'  => 'nullable|string|max:255',
            'active'         => 'nullable|boolean',
            'order'          => 'nullable|integer',
        ]);

        $testimonial->update($validated);

        return response()->json($testimonial->fresh());
    }

    public function uploadImage(Request $request, Testimonial $testimonial): JsonResponse
    {
        $this->authorizeManager($request);

        $request->validate(['image' => 'required|file|image|max:5120']);

        if ($testimonial->image && str_starts_with($testimonial->image, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $testimonial->image));
        }

        $path = $request->file('image')->store('testimonials', 'public');
        $url  = '/storage/' . $path;

        $testimonial->update(['image' => $url]);

        return response()->json(['image' => $url]);
    }

    public function destroy(Request $request, Testimonial $testimonial): JsonResponse
    {
        $this->authorizeManager($request);
        $testimonial->delete();

        return response()->json(['message' => 'Testimonial deleted.']);
    }

    private function authorizeManager(Request $request): void
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }
    }
}
