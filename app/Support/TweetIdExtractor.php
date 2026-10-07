<?php

namespace App\Support;

final class TweetIdExtractor
{
    public static function extract(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/^\d{1,25}$/', $url) === 1) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        $allowed = [
            'x.com',
            'twitter.com',
            'mobile.twitter.com',
            'fxtwitter.com',
            'vxtwitter.com',
            'fixupx.com',
        ];

        if (! in_array($host, $allowed, true)) {
            return null;
        }

        $path = $parts['path'] ?? '';

        if (preg_match('#/(?:[^/]+/status|i/web/status)/(\d{1,25})#', $path, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
