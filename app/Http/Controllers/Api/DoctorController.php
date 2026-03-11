<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Doctor::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize($request);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'slug'      => 'nullable|string|max:255|unique:doctors',
            'bio'       => 'nullable|string',
        ]);

        return response()->json(Doctor::create($validated), 201);
    }

    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        $this->authorize($request);

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'slug'      => 'nullable|string|max:255|unique:doctors,slug,' . $doctor->id,
            'bio'       => 'nullable|string',
        ]);

        $doctor->update($validated);

        return response()->json($doctor->fresh());
    }

    public function uploadImage(Request $request, Doctor $doctor): JsonResponse
    {
        $this->authorize($request);

        $request->validate(['image' => 'required|file|image|max:5120']);

        if ($doctor->image && str_starts_with($doctor->image, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $doctor->image));
        }

        $path = $request->file('image')->store('doctors', 'public');
        $url  = '/storage/' . $path;

        $doctor->update(['image' => $url]);

        return response()->json(['image' => $url]);
    }

    public function destroy(Request $request, Doctor $doctor): JsonResponse
    {
        $this->authorize($request);
        $doctor->delete();

        return response()->json(['message' => 'Doctor deleted.']);
    }

    private function authorize(Request $request): void
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }
    }
}
