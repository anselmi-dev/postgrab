<?php

namespace App\Jobs;

use App\Actions\Downloads\DownloadSlots;
use App\Exceptions\DownloadTooLargeException;
use App\Models\DownloadedFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BuildZipJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    /**
     * @param  list<int>  $fileIds
     */
    public function __construct(
        public int $zipFileId,
        public array $fileIds,
        public string $authorHandle,
        public string $tweetId,
        public ?string $slotSubject = null,
    ) {}

    public function handle(): void
    {
        $zipFile = DownloadedFile::query()->find($this->zipFileId);

        if (! $zipFile) {
            $this->releaseSlot();

            return;
        }

        try {
            $files = DownloadedFile::query()->whereIn('id', $this->fileIds)->get()->keyBy('id');
            $disk = Storage::disk($zipFile->disk);
            $total = 0;

            foreach ($this->fileIds as $id) {
                $file = $files->get($id);

                if (! $file?->isReady() || ! $disk->exists($file->path)) {
                    throw new \RuntimeException('A file for the ZIP is not ready.');
                }

                $total += (int) $file->size_bytes;
            }

            if ($total > (int) config('downloader.max_zip_bytes')) {
                throw new DownloadTooLargeException;
            }

            $zipPath = 'zips/'.$zipFile->uuid.'.zip';
            $disk->makeDirectory('zips');
            $absolute = $disk->path($zipPath);
            $zip = new ZipArchive;
            $opened = $zip->open($absolute, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($opened !== true) {
                throw new \RuntimeException('Could not open the ZIP archive.');
            }

            foreach ($this->fileIds as $id) {
                $file = $files->get($id);
                $name = $file->download_name;
                $zip->addFile($disk->path($file->path), $name);
                $zip->setCompressionName($name, ZipArchive::CM_STORE);
            }

            $zip->close();

            $zipFile->update([
                'path' => $zipPath,
                'extension' => 'zip',
                'size_bytes' => $disk->size($zipPath),
                'ready_at' => now(),
            ]);
        } finally {
            $this->releaseSlot();
        }
    }

    public function failed(): void
    {
        $this->releaseSlot();
    }

    private function releaseSlot(): void
    {
        if ($this->slotSubject) {
            app(DownloadSlots::class)->release($this->slotSubject);
            $this->slotSubject = null;
        }
    }
}
