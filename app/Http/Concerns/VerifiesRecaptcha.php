<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;

trait VerifiesRecaptcha
{
    protected function checkBotProtection(Request $request): void
    {
        // Honeypot — bots fill hidden fields, humans leave them empty
        if ($request->filled('website')) {
            throw ValidationException::withMessages([
                'website' => ['Cererea nu a putut fi procesată.'],
            ]);
        }

        // reCAPTCHA v3 — skip in local/testing environments if key not set
        $secret = env('RECAPTCHA_SECRET');
        if (!$secret || $secret === 'your-recaptcha-v3-secret-key') {
            return;
        }

        $token = $request->input('recaptcha_token');
        if (!$token) {
            throw ValidationException::withMessages([
                'recaptcha_token' => ['Verificarea reCAPTCHA a eșuat. Reîncarcă pagina și încearcă din nou.'],
            ]);
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        $result = $response->json();

        if (!($result['success'] ?? false) || ($result['score'] ?? 0) < 0.5) {
            throw ValidationException::withMessages([
                'recaptcha_token' => ['Verificarea reCAPTCHA a eșuat. Reîncarcă pagina și încearcă din nou.'],
            ]);
        }
    }
}
