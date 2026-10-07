<?php

namespace App\Services\TweetProviders;

use App\Contracts\TweetProvider;
use App\Data\TweetData;
use App\Exceptions\TweetNotFoundException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

class FxTwitterProvider implements TweetProvider
{
    public function __construct(private MediaNormalizer $media) {}

    public function name(): string
    {
        return 'fxtwitter';
    }

    public function fetch(string $tweetId): ?TweetData
    {
        $response = Http::timeout(15)
            ->acceptJson()
            ->get("https://api.fxtwitter.com/2/status/{$tweetId}");

        if ($response->status() === 404 || $response->json('code') === 404) {
            throw new TweetNotFoundException;
        }

        if (! $response->successful()) {
            return null;
        }

        $status = $response->json('status') ?? $response->json('tweet');

        if (! is_array($status)) {
            return null;
        }

        $author = is_array($status['author'] ?? null) ? $status['author'] : [];
        $media = is_array($status['media']['all'] ?? null) ? $status['media']['all'] : [];
        $createdAt = $status['created_at'] ?? null;

        return new TweetData(
            id: (string) ($status['id'] ?? $tweetId),
            url: (string) ($status['url'] ?? "https://x.com/i/web/status/{$tweetId}"),
            authorName: (string) ($author['name'] ?? $author['screen_name'] ?? 'X'),
            authorHandle: (string) ($author['screen_name'] ?? 'user'),
            avatarUrl: $author['avatar_url'] ?? null,
            createdAt: is_string($createdAt) ? $this->date($createdAt) : null,
            text: (string) ($status['text'] ?? ''),
            media: $this->media->items($media),
            driver: $this->name(),
        );
    }

    private function date(string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
