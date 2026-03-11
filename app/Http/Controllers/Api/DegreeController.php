<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Degree;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DegreeController extends Controller {
    public function index(Request $request, User $user): JsonResponse {
        if (!$request->user()?->isAdmin() && $request->user()?->id !== $user->id) abort(403);
        return response()->json($user->degrees()->with(['event', 'uploader'])->orderByDesc('created_at')->get());
    }

    public function store(Request $request, User $user): JsonResponse {
        if (!$request->user()?->isAdmin()) abort(403);
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'event_id' => 'nullable|exists:events,id',
            'file'     => 'required|file|mimes:pdf|max:10240',
        ]);
        $file = $request->file('file');
        $path = $file->store("degrees/{$user->id}", 'public');
        $degree = Degree::create([
            'user_id'     => $user->id,
            'event_id'    => $validated['event_id'] ?? null,
            'title'       => $validated['title'],
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'uploaded_by' => $request->user()->id,
        ]);
        return response()->json($degree->load(['event', 'uploader']), 201);
    }

    public function download(Request $request, Degree $degree): StreamedResponse {
        $user = $request->user();
        if (!$user || ($degree->user_id !== $user->id && !$user->isAdmin())) abort(403);
        if (!Storage::disk('public')->exists($degree->file_path)) abort(404);
        return Storage::disk('public')->download($degree->file_path, $degree->file_name);
    }

    public function destroy(Request $request, Degree $degree): JsonResponse {
        if (!$request->user()?->isAdmin()) abort(403);
        Storage::disk('public')->delete($degree->file_path);
        $degree->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
