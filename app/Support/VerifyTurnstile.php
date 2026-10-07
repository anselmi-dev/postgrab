<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class VerifyTurnstile
{
    public function passes(?string $token): bool
    {
        $secret = config('downloader.turnstile_secret');
        $siteKey = config('downloader.turnstile_site_key');

        if (! filled($siteKey) || ! filled($secret)) {
            return false;
        }

        if (! filled($token)) {
            return false;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);

        return (bool) $response->json('success');
    }

    public function configured(): bool
    {
        return filled(config('downloader.turnstile_site_key')) && filled(config('downloader.turnstile_secret'));
    }
}
