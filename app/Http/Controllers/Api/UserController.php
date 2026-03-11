<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        return response()->json(User::orderBy('name')->get());
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if (! $request->user()?->isAdmin() && $request->user()?->id !== $user->id) {
            abort(403);
        }

        return response()->json($user);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|in:participant,doctor,admin,events_manager',
        ]);

        $user->update($validated);

        return response()->json($user->fresh());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
