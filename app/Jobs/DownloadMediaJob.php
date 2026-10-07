<?php

namespace App\Jobs;

use App\Actions\Downloads\DownloadSlots;
use App\Exceptions\DownloadTooLargeException;
use App\Exceptions\UnsafeMediaUrlException;
use App\Models\DownloadedFile;
use App\Support\TwimgUrl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class DownloadMediaJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 2;

    public function __construct(
        public string $sourceUrl,
        public int $downloadedFileId,
        public ?string $slotSubject = null,
    ) {}

    public function handle(): void
    {
        $file = DownloadedFile::query()->find($this->downloadedFileId);

        if (! $file || $file->isReady()) {
            $this->releaseSlot();

            return;
        }

        try {
            TwimgUrl::assert($this->sourceUrl);
            $this->download($file);
        } finally {
            $this->releaseSlot();
        }
    }

    public function failed(): void
    {
        $this->releaseSlot();
    }

    private function download(DownloadedFile $file): void
    {
        $max = (int) config('downloader.max_file_bytes');
        $length = $this->contentLength();

        if ($length !== null && $length > $max) {
            throw new DownloadTooLargeException;
        }

        $temporary = tempnam(sys_get_temp_dir(), 'pg');

        try {
            $response = $this->http()->sink($temporary)->get($this->sourceUrl);

            if (! $response->successful()) {
                throw new \RuntimeException('Media download failed with status '.$response->status());
            }

            $size = filesize($temporary) ?: 0;

            if ($size < 1 || $size > $max) {
                throw new DownloadTooLargeException;
            }

            $path = 'files/'.$file->uuid.'.'.$file->extension;
            $stream = fopen($temporary, 'r');
            Storage::disk($file->disk)->writeStream($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $file->update([
                'path' => $path,
                'size_bytes' => $size,
                'ready_at' => now(),
            ]);
        } finally {
            if (is_string($temporary) && file_exists($temporary)) {
                unlink($temporary);
            }
        }
    }

    private function contentLength(): ?int
    {
        try {
            $head = $this->http()->head($this->sourceUrl);
        } catch (\Throwable) {
            return null;
        }

        $length = $head->header('Content-Length');

        if (! is_numeric($length)) {
            return null;
        }

        return (int) $length;
    }

    private function http(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->withHeaders(['User-Agent' => 'Postgrab/1.0'])
            ->withOptions([
                'allow_redirects' => [
                    'max' => 3,
                    'protocols' => ['https'],
                    'on_redirect' => function (RequestInterface $request, ResponseInterface $response, UriInterface $uri): void {
                        if (! TwimgUrl::isTwimgHost($uri->getHost())) {
                            throw new UnsafeMediaUrlException($uri->__toString());
                        }
                    },
                ],
            ]);
    }

    private function releaseSlot(): void
    {
        if ($this->slotSubject) {
            app(DownloadSlots::class)->release($this->slotSubject);
            $this->slotSubject = null;
        }
    }
}
