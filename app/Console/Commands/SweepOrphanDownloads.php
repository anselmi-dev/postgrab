<?php

namespace App\Console\Commands;

use App\Models\DownloadedFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SweepOrphanDownloads extends Command
{
    protected $signature = 'downloads:sweep-orphans';

    protected $description = 'Delete download files older than 25 hours that have no database row';

    public function handle(): int
    {
        $disk = Storage::disk('downloads');
        $cutoff = now()->subHours(25)->getTimestamp();
        $removed = 0;

        foreach ($disk->allFiles() as $path) {
            if ($disk->lastModified($path) > $cutoff) {
                continue;
            }

            $known = DownloadedFile::query()->where('path', $path)->exists();

            if (! $known) {
                $disk->delete($path);
                $removed++;
            }
        }

        $this->info("Removed {$removed} orphan files.");

        return self::SUCCESS;
    }
}
