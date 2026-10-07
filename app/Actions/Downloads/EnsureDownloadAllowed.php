<?php

namespace App\Actions\Downloads;

use App\Exceptions\TooManyDownloadsException;
use App\Models\DownloadBlock;
use App\Settings\DownloaderSettings;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class EnsureDownloadAllowed
{
    public function __construct(private DownloaderSettings $settings) {}

    public function handle(Request $request, int $units = 1): void
    {
        $units = max(1, $units);
        $key = $this->key($request);

        if ($until = Cache::get("{$key}:lock")) {
            $until = $until instanceof CarbonInterface ? $until : Carbon::parse($until);

            if ($until->isFuture()) {
                throw new TooManyDownloadsException($until);
            }
        }

        $hourMax = $request->user() ? $this->settings->user_downloads_per_hour : $this->settings->guest_downloads_per_hour;
        $dayMax = $request->user() ? $this->settings->user_downloads_per_day : $this->settings->guest_downloads_per_day;
        $hourKey = "{$key}:hour";
        $dayKey = "{$key}:day";

        if (RateLimiter::attempts($hourKey) + $units > $hourMax || RateLimiter::attempts($dayKey) + $units > $dayMax) {
            $this->strike($request, $key);

            return;
        }

        for ($i = 0; $i < $units; $i++) {
            RateLimiter::hit($hourKey, 3600);
            RateLimiter::hit($dayKey, 86400);
        }
    }

    public function remaining(Request $request): int
    {
        $key = $this->key($request);
        $hourMax = $request->user() ? $this->settings->user_downloads_per_hour : $this->settings->guest_downloads_per_hour;

        return max(0, $hourMax - RateLimiter::attempts("{$key}:hour"));
    }

    public function lockUntil(Request $request): ?CarbonInterface
    {
        $until = Cache::get($this->key($request).':lock');

        if (! $until) {
            return null;
        }

        $until = $until instanceof CarbonInterface ? $until : Carbon::parse($until);

        return $until->isFuture() ? $until : null;
    }

    public function subject(Request $request): string
    {
        return $request->user() ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
    }

    private function strike(Request $request, string $key): void
    {
        $penalties = config('downloader.penalties');
        $strikes = (int) Cache::get("{$key}:strikes", 0) + 1;
        Cache::put("{$key}:strikes", $strikes, now()->addDay());

        $seconds = $penalties[min($strikes, count($penalties)) - 1];
        $until = now()->addSeconds($seconds);
        Cache::put("{$key}:lock", $until, $seconds);

        DownloadBlock::query()->updateOrCreate(
            ['subject' => $this->subject($request)],
            ['strikes' => $strikes, 'locked_until' => $until],
        );

        Log::channel('abuse')->warning('download_limited', [
            'subject' => $this->subject($request),
            'ip' => $request->ip(),
            'strikes' => $strikes,
            'until' => $until->toIso8601String(),
        ]);

        throw new TooManyDownloadsException($until);
    }

    private function key(Request $request): string
    {
        return 'dl:'.($request->user()?->id ?? $request->ip());
    }
}
