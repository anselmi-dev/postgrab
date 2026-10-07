<?php

namespace App\Http\Controllers;

use App\Models\DownloadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadedFilePreviewController
{
    public function __invoke(DownloadedFile $file): StreamedResponse
    {
        abort_unless($file->isPreviewableImage(), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->download_name, [
            'Content-Type' => self::mime($file->extension),
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private static function mime(?string $extension): string
    {
        return match (strtolower((string) $extension)) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
