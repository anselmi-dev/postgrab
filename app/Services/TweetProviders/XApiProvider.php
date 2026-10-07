<?php

namespace App\Services\TweetProviders;

use App\Contracts\TweetProvider;
use App\Data\TweetData;
use App\Exceptions\TweetNotFoundException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

class XApiProvider implements TweetProvider
{
    public function __construct(private MediaNormalizer $media) {}

    public function name(): string
    {
        return 'xapi';
    }

    public function fetch(string $tweetId): ?TweetData
    {
        $token = config('downloader.x_bearer_token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        $response = Http::timeout(15)
            ->withToken($token)
            ->acceptJson()
            ->get("https://api.x.com/2/tweets/{$tweetId}", [
                'expansions' => 'author_id,attachments.media_keys',
                'tweet.fields' => 'created_at,text,attachments',
                'user.fields' => 'name,username,profile_image_url',
                'media.fields' => 'url,preview_image_url,type,variants,width,height,duration_ms',
            ]);

        if ($response->status() === 404) {
            throw new TweetNotFoundException;
        }

        if (! $response->successful()) {
            return null;
        }

        $tweet = $response->json('data');

        if (! is_array($tweet)) {
            return null;
        }

        $users = collect($response->json('includes.users') ?? []);
        $author = $users->firstWhere('id', $tweet['author_id'] ?? null) ?? [];
        $mediaKeys = $tweet['attachments']['media_keys'] ?? [];
        $includes = collect($response->json('includes.media') ?? [])->keyBy('media_key');
        $items = [];

        foreach ($mediaKeys as $key) {
            $media = $includes->get($key);

            if (! is_array($media)) {
                continue;
            }

            $duration = isset($media['duration_ms']) ? ((float) $media['duration_ms'] / 1000) : null;
            $variants = array_map(function (array $variant) use ($duration): array {
                $variant['duration'] = $duration;

                return $variant;
            }, array_filter($media['variants'] ?? [], 'is_array'));

            $items[] = [
                'id' => (string) $key,
                'type' => $media['type'] ?? 'photo',
                'url' => $media['url'] ?? null,
                'thumbnail_url' => $media['preview_image_url'] ?? $media['url'] ?? null,
                'width' => $media['width'] ?? null,
                'height' => $media['height'] ?? null,
                'variants' => $variants,
            ];
        }

        $handle = (string) ($author['username'] ?? 'user');

        return new TweetData(
            id: (string) ($tweet['id'] ?? $tweetId),
            url: "https://x.com/{$handle}/status/{$tweetId}",
            authorName: (string) ($author['name'] ?? $handle),
            authorHandle: $handle,
            avatarUrl: $author['profile_image_url'] ?? null,
            createdAt: isset($tweet['created_at']) ? CarbonImmutable::parse($tweet['created_at']) : null,
            text: (string) ($tweet['text'] ?? ''),
            media: $this->media->items($items),
            driver: $this->name(),
        );
    }
}
