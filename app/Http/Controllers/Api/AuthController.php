<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Concerns\VerifiesRecaptcha;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use VerifiesRecaptcha;
    public function register(Request $request): JsonResponse
    {
        $this->checkBotProtection($request);

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'first_name'        => 'nullable|string|max:255',
            'last_name'         => 'nullable|string|max:255',
            'email'             => 'required|string|email|max:255|unique:users',
            'password'          => ['required', 'confirmed', PasswordRule::defaults()],
            'phone'             => 'nullable|string|max:30',
            'specialty'         => 'nullable|string|max:100',
            'professional_grade'=> 'nullable|string|max:100',
            'cuim'              => [
                in_array($request->input('professional_grade'), ['medic-specialist', 'medic-primar'])
                    && $request->input('specialty') !== 'rezidenti'
                    ? 'required' : 'nullable',
                'string',
                'max:30',
            ],
        ]);

        $user = User::create([
            ...$validated,
            'role' => 'participant',
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'first_name'        => 'nullable|string|max:255',
            'last_name'         => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:30',
            'specialty'         => 'nullable|string|max:100',
            'professional_grade'=> 'nullable|string|max:100',
            'cuim'              => [
                in_array($request->input('professional_grade'), ['medic-specialist', 'medic-primar'])
                    && $request->input('specialty') !== 'rezidenti'
                    ? 'required' : 'nullable',
                'string',
                'max:30',
            ],
        ]);

        $request->user()->update($validated);

        return response()->json($request->user()->fresh());
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        // Always return success to avoid email enumeration
        return response()->json(['message' => 'If this email is registered, a reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Parola a fost resetată cu succes.']);
    }
}
