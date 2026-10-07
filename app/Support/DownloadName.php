<?php

namespace App\Support;

final class DownloadName
{
    public static function file(string $handle, string $tweetId, int $index, string $extension): string
    {
        return sprintf('%s_%s_%d.%s', self::handle($handle), $tweetId, $index, $extension);
    }

    public static function zip(string $handle, string $tweetId): string
    {
        return sprintf('%s_%s.zip', self::handle($handle), $tweetId);
    }

    public static function handle(string $handle): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', $handle) ?? '';

        return $clean !== '' ? $clean : 'post';
    }
}
