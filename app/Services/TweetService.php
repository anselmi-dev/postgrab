<?php

namespace App\Services;

use App\Contracts\TweetProvider;
use App\Data\TweetData;
use App\Exceptions\InvalidTweetUrlException;
use App\Exceptions\TooManyLookupsException;
use App\Exceptions\TweetBlockedException;
use App\Exceptions\TweetHasNoMediaException;
use App\Exceptions\TweetNotFoundException;
use App\Exceptions\TweetUnavailableException;
use App\Models\BlockedTweet;
use App\Models\UsageEvent;
use App\Services\TweetProviders\FxTwitterProvider;
use App\Services\TweetProviders\SyndicationProvider;
use App\Services\TweetProviders\XApiProvider;
use App\Settings\DownloaderSettings;
use App\Support\TweetIdExtractor;
use App\Support\VerifyTurnstile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class TweetService
{
    public function __construct(
        private DownloaderSettings $settings,
        private VerifyTurnstile $turnstile,
    ) {}

    public function lookup(string $url, Request $request, ?string $turnstileToken = null): TweetData
    {
        $this->ensureLookupAllowed($request, $turnstileToken);

        $id = TweetIdExtractor::extract($url);

        if ($id === null) {
            $this->record($request, null, null, false);

            throw new InvalidTweetUrlException;
        }

        if (BlockedTweet::query()->where('tweet_id', $id)->exists()) {
            $this->record($request, $id, null, false);

            throw new TweetBlockedException;
        }

        $cached = Cache::get($this->cacheKey($id));

        if (is_array($cached)) {
            $tweet = TweetData::fromArray($cached);
            $tweet = new TweetData(
                id: $tweet->id,
                url: $tweet->url,
                authorName: $tweet->authorName,
                authorHandle: $tweet->authorHandle,
                avatarUrl: $tweet->avatarUrl,
                createdAt: $tweet->createdAt,
                text: $tweet->text,
                media: $tweet->media,
                driver: 'cache',
            );
            $this->record($request, $id, 'cache', true);
            $this->guardMedia($tweet);

            return $tweet;
        }

        $notFound = false;

        foreach ($this->drivers() as $driver) {
            try {
                $tweet = $driver->fetch($id);
            } catch (TweetNotFoundException) {
                $notFound = true;
                $this->record($request, $id, $driver->name(), false);

                continue;
            } catch (\Throwable $exception) {
                report($exception);
                $this->record($request, $id, $driver->name(), false);

                continue;
            }

            if (! $tweet instanceof TweetData) {
                $this->record($request, $id, $driver->name(), false);

                continue;
            }

            Cache::put($this->cacheKey($id), $tweet->toArray(), now()->addSeconds((int) config('downloader.cache_ttl_seconds')));
            $this->record($request, $id, $driver->name(), true);
            $this->guardMedia($tweet);

            return $tweet;
        }

        throw $notFound ? new TweetNotFoundException : new TweetUnavailableException;
    }

    /**
     * @return list<TweetProvider>
     */
    private function drivers(): array
    {
        $map = [
            'fxtwitter' => FxTwitterProvider::class,
            'syndication' => SyndicationProvider::class,
            'xapi' => XApiProvider::class,
        ];

        $drivers = [];

        foreach ($this->settings->driverNames() as $name) {
            if (isset($map[$name])) {
                $drivers[] = app($map[$name]);
            }
        }

        return $drivers;
    }

    private function ensureLookupAllowed(Request $request, ?string $turnstileToken): void
    {
        $key = 'lookup:'.($request->user()?->id ?? $request->ip());
        $max = $request->user()
            ? $this->settings->user_lookups_per_minute
            : $this->settings->guest_lookups_per_minute;

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $configured = filled(config('downloader.turnstile_site_key'));

            if ($configured && $this->turnstile->passes($turnstileToken)) {
                return;
            }

            Log::channel('abuse')->warning('lookup_limited', [
                'subject' => $key,
                'ip' => $request->ip(),
            ]);

            throw new TooManyLookupsException(challenge: $configured);
        }

        RateLimiter::hit($key, 60);
    }

    private function guardMedia(TweetData $tweet): void
    {
        if ($tweet->media === []) {
            throw new TweetHasNoMediaException;
        }
    }

    private function record(Request $request, ?string $tweetId, ?string $driver, bool $success): void
    {
        UsageEvent::query()->create([
            'type' => 'lookup',
            'tweet_id' => $tweetId,
            'driver' => $driver,
            'success' => $success,
            'user_id' => $request->user()?->id,
        ]);
    }

    private function cacheKey(string $id): string
    {
        return 'tweet:'.$id;
    }
}
