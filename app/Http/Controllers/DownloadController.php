<?php

namespace App\Http\Controllers;

use App\Models\DownloadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController
{
    public function __invoke(DownloadedFile $file): StreamedResponse
    {
        abort_unless($file->isReady(), 404);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->download_name, [
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
