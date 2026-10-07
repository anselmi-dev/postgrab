<?php

namespace App\Support;

use App\Models\DownloadedFile;
use Filament\Facades\Filament;

final class PostPreview
{
    public static function url(?string $stored, string $tweetId): string
    {
        return self::https($stored) ?? 'https://x.com/i/web/status/'.preg_replace('/\D/', '', $tweetId);
    }

    public static function image(?string $url): ?string
    {
        return self::https($url);
    }

    public static function file(DownloadedFile $file): ?string
    {
        $remote = self::https($file->getAttribute('preview_thumb'));

        if ($remote !== null) {
            return $remote;
        }

        if (! $file->isPreviewableImage()) {
            return null;
        }

        return Filament::getPanel('admin')->route('files.preview', ['file' => $file]);
    }

    private static function https(?string $url): ?string
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return parse_url($url, PHP_URL_SCHEME) === 'https' ? $url : null;
    }
}
