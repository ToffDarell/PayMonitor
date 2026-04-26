<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (blank($token) || blank(config('services.recaptcha.secret_key'))) {
            return false;
        }

        $response = Http::asForm()->post((string) config('services.recaptcha.verify_url'), [
            'secret' => (string) config('services.recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        if (! $response->successful()) {
            return false;
        }

        return (bool) $response->json('success', false);
    }
}
