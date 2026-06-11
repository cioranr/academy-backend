<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionnaireController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        return response()->json(Questionnaire::with('questions')->orderByDesc('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions'   => 'nullable|array',
            'questions.*.question' => 'required|string',
            'questions.*.type'     => 'required|in:text,radio,checkbox,rating',
            'questions.*.options'  => 'nullable|array',
            'questions.*.required' => 'boolean',
            'questions.*.order'    => 'integer',
        ]);

        $questionnaire = Questionnaire::create([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        foreach ($validated['questions'] ?? [] as $i => $q) {
            $questionnaire->questions()->create([
                'question' => $q['question'],
                'type'     => $q['type'],
                'options'  => $q['options'] ?? null,
                'required' => $q['required'] ?? true,
                'order'    => $q['order'] ?? $i,
            ]);
        }

        return response()->json($questionnaire->load('questions'), 201);
    }

    public function show(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        return response()->json($questionnaire->load('questions'));
    }

    public function update(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'questions'   => 'nullable|array',
            'questions.*.id'       => 'nullable|integer',
            'questions.*.question' => 'required|string',
            'questions.*.type'     => 'required|in:text,radio,checkbox,rating',
            'questions.*.options'  => 'nullable|array',
            'questions.*.required' => 'boolean',
            'questions.*.order'    => 'integer',
        ]);

        $questionnaire->update([
            'title'       => $validated['title'] ?? $questionnaire->title,
            'description' => $validated['description'] ?? $questionnaire->description,
        ]);

        if (isset($validated['questions'])) {
            // Replace all questions
            $questionnaire->questions()->delete();
            foreach ($validated['questions'] as $i => $q) {
                $questionnaire->questions()->create([
                    'question' => $q['question'],
                    'type'     => $q['type'],
                    'options'  => $q['options'] ?? null,
                    'required' => $q['required'] ?? true,
                    'order'    => $q['order'] ?? $i,
                ]);
            }
        }

        return response()->json($questionnaire->fresh()->load('questions'));
    }

    public function destroy(Request $request, Questionnaire $questionnaire): JsonResponse
    {
        if (! $request->user()?->isEventsManager()) {
            abort(403);
        }

        $questionnaire->delete();

        return response()->json(['message' => 'Chestionarul a fost șters.']);
    }
}
