<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpecialtyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Specialty::orderBy('order')->orderBy('label')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'nullable|string|max:100|alpha_dash|unique:specialties,value',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['value'] ??= Str::slug($validated['label']);
        if (Specialty::where('value', $validated['value'])->exists()) {
            abort(422, 'Există deja o specialitate cu acest cod.');
        }
        $validated['order'] ??= (int) Specialty::max('order') + 10;

        return response()->json(Specialty::create($validated), 201);
    }

    public function update(Request $request, Specialty $specialty): JsonResponse
    {
        $this->authorizeManager($request);

        // The value is stored on users/registrations, so only the label and order are editable.
        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'order' => 'nullable|integer|min:0',
        ]);

        $specialty->update($validated);

        return response()->json($specialty->fresh());
    }

    public function destroy(Request $request, Specialty $specialty): JsonResponse
    {
        $this->authorizeManager($request);

        if (in_array($specialty->value, ['rezidenti', 'alta'], true)) {
            abort(422, 'Această specialitate este folosită de sistem și nu poate fi ștearsă.');
        }

        $specialty->delete();

        return response()->json(['message' => 'Specialty deleted.']);
    }

    private function authorizeManager(Request $request): void
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }
    }
}
