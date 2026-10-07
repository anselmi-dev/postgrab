<?php

namespace App\Support;

use App\Exceptions\UnsafeMediaUrlException;

final class TwimgUrl
{
    public static function assert(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || ! self::isTwimgHost($host)) {
            throw new UnsafeMediaUrlException($url);
        }
    }

    public static function isTwimgHost(string $host): bool
    {
        $host = strtolower($host);

        return $host === 'twimg.com' || str_ends_with($host, '.twimg.com');
    }
}
