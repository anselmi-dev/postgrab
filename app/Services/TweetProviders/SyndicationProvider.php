<?php

namespace App\Services\TweetProviders;

use App\Contracts\TweetProvider;
use App\Data\TweetData;
use App\Exceptions\TweetNotFoundException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

class SyndicationProvider implements TweetProvider
{
    public function __construct(private MediaNormalizer $media) {}

    public function name(): string
    {
        return 'syndication';
    }

    public function fetch(string $tweetId): ?TweetData
    {
        $token = str_replace(['0', '.'], '', base_convert((string) (int) (((float) $tweetId / 1e15) * M_PI), 10, 36));

        $response = Http::timeout(15)
            ->acceptJson()
            ->get('https://cdn.syndication.twimg.com/tweet-result', [
                'id' => $tweetId,
                'lang' => 'en',
                'token' => $token,
            ]);

        if ($response->status() === 404) {
            throw new TweetNotFoundException;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || isset($payload['tombstone']) || ($payload['__typename'] ?? null) === 'TweetTombstone') {
            throw new TweetNotFoundException;
        }

        $user = is_array($payload['user'] ?? null) ? $payload['user'] : [];
        $items = $this->mediaItems($payload);

        return new TweetData(
            id: (string) ($payload['id_str'] ?? $tweetId),
            url: 'https://x.com/'.($user['screen_name'] ?? 'i').'/status/'.$tweetId,
            authorName: (string) ($user['name'] ?? $user['screen_name'] ?? 'X'),
            authorHandle: (string) ($user['screen_name'] ?? 'user'),
            avatarUrl: $user['profile_image_url_https'] ?? null,
            createdAt: isset($payload['created_at']) ? CarbonImmutable::parse($payload['created_at']) : null,
            text: (string) ($payload['text'] ?? ''),
            media: $this->media->items($items),
            driver: $this->name(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function mediaItems(array $payload): array
    {
        $items = [];

        foreach ($payload['photos'] ?? [] as $photo) {
            if (! is_array($photo)) {
                continue;
            }

            $items[] = [
                'id' => (string) ($photo['id'] ?? count($items) + 1),
                'type' => 'photo',
                'url' => $photo['url'] ?? null,
                'width' => $photo['width'] ?? null,
                'height' => $photo['height'] ?? null,
                'thumbnail_url' => $photo['url'] ?? null,
            ];
        }

        $video = $payload['video'] ?? null;

        if (is_array($video)) {
            $items[] = [
                'id' => (string) ($video['videoId']['id'] ?? 'video'),
                'type' => (($video['contentType'] ?? '') === 'gif' || ($payload['mediaDetails'][0]['type'] ?? '') === 'animated_gif') ? 'gif' : 'video',
                'thumbnail_url' => $video['poster'] ?? null,
                'duration' => isset($video['durationMs']) ? ((float) $video['durationMs'] / 1000) : null,
                'variants' => array_map(function (array $variant): array {
                    return [
                        'url' => $variant['src'] ?? null,
                        'content_type' => $variant['type'] ?? null,
                        'bitrate' => $variant['bitrate'] ?? null,
                        'duration' => null,
                    ];
                }, array_filter($video['variants'] ?? [], 'is_array')),
            ];
        }

        return $items;
    }
}
