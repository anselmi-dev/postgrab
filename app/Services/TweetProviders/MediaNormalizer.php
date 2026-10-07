<?php

namespace App\Services\TweetProviders;

use App\Data\MediaItem;
use App\Data\MediaVariant;
use App\Exceptions\UnsafeMediaUrlException;
use App\Support\TwimgUrl;

final class MediaNormalizer
{
    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<MediaItem>
     */
    public function items(array $items): array
    {
        $media = [];
        $index = 1;

        foreach ($items as $item) {
            $normalized = $this->item($item, $index);

            if ($normalized instanceof MediaItem) {
                $media[] = $normalized;
                $index++;
            }
        }

        return $media;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function item(array $item, int $index): ?MediaItem
    {
        $type = strtolower((string) ($item['type'] ?? 'photo'));

        if (in_array($type, ['animated_gif', 'gif'], true)) {
            $type = 'gif';
        }

        if (! in_array($type, ['photo', 'video', 'gif'], true)) {
            return null;
        }

        $variants = $this->variants($item, $type);

        if ($variants === []) {
            return null;
        }

        return new MediaItem(
            id: (string) ($item['id'] ?? $item['media_key'] ?? $index),
            index: $index,
            type: $type,
            thumbnailUrl: $this->safeUrl($item['thumbnail_url'] ?? $item['preview_image_url'] ?? $item['poster'] ?? null),
            variants: $variants,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return list<MediaVariant>
     */
    private function variants(array $item, string $type): array
    {
        $raw = $item['variants'] ?? $item['formats'] ?? [];
        $variants = [];

        foreach ($raw as $format) {
            if (! is_array($format)) {
                continue;
            }

            $variant = $this->variant($format, $type);

            if ($variant instanceof MediaVariant) {
                $variants[$variant->key] = $variant;
            }
        }

        if ($variants === [] && $type === 'photo') {
            $url = $this->safeUrl($item['url'] ?? null);

            if ($url !== null) {
                $variants['original'] = new MediaVariant(
                    key: 'original',
                    url: $url,
                    label: 'original',
                    width: isset($item['width']) ? (int) $item['width'] : null,
                    height: isset($item['height']) ? (int) $item['height'] : null,
                    bitrate: null,
                    sizeBytes: null,
                    contentType: 'image/jpeg',
                    extension: $this->extension($url, 'jpg'),
                );
            }
        }

        if ($variants === [] && in_array($type, ['video', 'gif'], true)) {
            $url = $this->safeUrl($item['url'] ?? null);

            if ($url !== null && ! str_contains($url, '.m3u8')) {
                [$width, $height] = $this->dimensions($url, $item);
                $variants[$this->key($width, $height, null)] = new MediaVariant(
                    key: $this->key($width, $height, null),
                    url: $url,
                    label: $this->label($width, $height, null),
                    width: $width,
                    height: $height,
                    bitrate: null,
                    sizeBytes: null,
                    contentType: 'video/mp4',
                    extension: 'mp4',
                );
            }
        }

        return array_values($variants);
    }

    /**
     * @param  array<string, mixed>  $format
     */
    private function variant(array $format, string $type): ?MediaVariant
    {
        $container = strtolower((string) ($format['container'] ?? ''));
        $contentType = strtolower((string) ($format['content_type'] ?? $format['type'] ?? ''));
        $url = $this->safeUrl($format['url'] ?? $format['src'] ?? null);

        if ($url === null || str_contains($url, '.m3u8') || $container === 'm3u8' || str_contains($contentType, 'mpegurl')) {
            return null;
        }

        $isVideo = in_array($type, ['video', 'gif'], true);

        if ($isVideo && $container !== '' && $container !== 'mp4' && ! str_contains($contentType, 'mp4') && ! str_ends_with(parse_url($url, PHP_URL_PATH) ?: '', '.mp4')) {
            return null;
        }

        [$width, $height] = $this->dimensions($url, $format);
        $bitrate = isset($format['bitrate']) ? (int) $format['bitrate'] : (isset($format['bit_rate']) ? (int) $format['bit_rate'] : null);
        $duration = isset($format['duration']) ? (float) $format['duration'] : null;
        $size = null;

        if ($bitrate && $duration) {
            $size = (int) round($bitrate * $duration / 8);
        }

        $extension = $isVideo ? 'mp4' : $this->extension($url, 'jpg');
        $key = $isVideo ? $this->key($width, $height, $bitrate) : 'original';

        return new MediaVariant(
            key: $key,
            url: $url,
            label: $isVideo ? $this->label($width, $height, $bitrate) : 'original',
            width: $width,
            height: $height,
            bitrate: $bitrate,
            sizeBytes: $size,
            contentType: $isVideo ? 'video/mp4' : ($contentType !== '' ? $contentType : 'image/jpeg'),
            extension: $extension,
        );
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array{0: ?int, 1: ?int}
     */
    private function dimensions(string $url, array $source): array
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');

        if (preg_match('#/(\d{2,5})x(\d{2,5})/#', $path, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        $width = isset($source['width']) ? (int) $source['width'] : null;
        $height = isset($source['height']) ? (int) $source['height'] : null;

        return [$width, $height];
    }

    private function key(?int $width, ?int $height, ?int $bitrate): string
    {
        if ($width && $height) {
            return $width.'x'.$height;
        }

        return 'b'.($bitrate ?? 0);
    }

    private function label(?int $width, ?int $height, ?int $bitrate): string
    {
        if ($width && $height) {
            return $width.'x'.$height;
        }

        if ($bitrate) {
            return (int) round($bitrate / 1000).'kbps';
        }

        return 'mp4';
    }

    private function extension(string $url, string $default): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'mp4'], true) ? ($ext === 'jpeg' ? 'jpg' : $ext) : $default;
    }

    private function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        try {
            TwimgUrl::assert($url);
        } catch (UnsafeMediaUrlException) {
            return null;
        }

        return $url;
    }
}
