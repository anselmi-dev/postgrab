<?php

namespace App\Actions\Downloads;

use App\Data\MediaItem;
use App\Data\TweetData;
use App\Exceptions\DownloadTooLargeException;
use App\Jobs\BuildZipJob;
use App\Jobs\DownloadMediaJob;
use App\Models\DownloadedFile;
use App\Models\UsageEvent;
use App\Settings\DownloaderSettings;
use App\Support\DownloadName;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RequestZipDownload
{
    public function __construct(
        private EnsureDownloadAllowed $limits,
        private DownloadSlots $slots,
        private DownloaderSettings $settings,
    ) {}

    /**
     * @param  list<array{id: string, quality: ?string}>  $selection
     */
    public function handle(Request $request, TweetData $tweet, array $selection): DownloadedFile
    {
        $items = [];

        foreach ($selection as $chosen) {
            $media = $tweet->mediaById($chosen['id']);

            if (! $media instanceof MediaItem) {
                continue;
            }

            $variant = $media->variant($chosen['quality'] ?? '') ?? $media->bestVariant();

            if ($variant) {
                $items[] = ['media' => $media, 'variant' => $variant];
            }
        }

        if ($items === []) {
            throw new \RuntimeException('Nothing selected.');
        }

        $this->limits->handle($request, count($items));

        $hash = substr(hash('sha256', collect($items)->map(fn (array $item) => $item['media']->id.':'.$item['variant']->key)->sort()->implode('|')), 0, 16);
        $variantKey = 'zip:'.$hash;
        $existing = DownloadedFile::query()
            ->where('tweet_id', $tweet->id)
            ->where('variant', $variantKey)
            ->where('expires_at', '>', now())
            ->first();

        UsageEvent::query()->create([
            'type' => 'download',
            'tweet_id' => $tweet->id,
            'success' => true,
            'user_id' => $request->user()?->id,
            'units' => count($items),
        ]);

        if ($existing?->isReady()) {
            return $existing;
        }

        $estimated = 0;

        foreach ($items as $item) {
            $estimated += (int) ($item['variant']->sizeBytes ?? 0);
        }

        if ($estimated > (int) config('downloader.max_zip_bytes')) {
            throw new DownloadTooLargeException;
        }

        $zip = $existing ?? DownloadedFile::query()->create([
            'uuid' => (string) Str::uuid(),
            'tweet_id' => $tweet->id,
            'variant' => $variantKey,
            'disk' => 'downloads',
            'extension' => 'zip',
            'download_name' => DownloadName::zip($tweet->authorHandle, $tweet->id),
            'expires_at' => now()->addHours($this->settings->file_ttl_hours),
        ]);

        $subject = $this->limits->subject($request);
        $queue = $request->user() ? 'downloads-high' : 'downloads';
        $connection = $this->connection();
        $fileIds = [];
        $jobs = [];

        foreach ($items as $item) {
            $media = $item['media'];
            $variant = $item['variant'];
            $key = $media->type.':'.$media->id.':'.$variant->key;
            $file = DownloadedFile::query()
                ->where('tweet_id', $tweet->id)
                ->where('variant', $key)
                ->where('expires_at', '>', now())
                ->first();

            if (! $file) {
                $file = DownloadedFile::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'tweet_id' => $tweet->id,
                    'variant' => $key,
                    'disk' => 'downloads',
                    'extension' => $variant->extension,
                    'download_name' => DownloadName::file($tweet->authorHandle, $tweet->id, $media->index, $variant->extension),
                    'expires_at' => now()->addHours($this->settings->file_ttl_hours),
                ]);
            }

            $fileIds[] = $file->id;

            if (! $file->isReady()) {
                $jobs[] = new DownloadMediaJob($variant->url, $file->id);
            }
        }

        $this->slots->acquire($subject, $request->user() !== null);

        if ($jobs === []) {
            BuildZipJob::dispatch($zip->id, $fileIds, $tweet->authorHandle, $tweet->id, $subject)
                ->onConnection($connection)
                ->onQueue($queue);

            return $zip;
        }

        $zipId = $zip->id;
        $handle = $tweet->authorHandle;
        $tweetId = $tweet->id;

        $batch = Bus::batch($jobs)
            ->name('zip-'.$tweetId)
            ->then(function (Batch $batch) use ($zipId, $fileIds, $handle, $tweetId, $subject, $queue, $connection): void {
                BuildZipJob::dispatch($zipId, $fileIds, $handle, $tweetId, $subject)
                    ->onConnection($connection)
                    ->onQueue($queue);
            })
            ->catch(function () use ($subject, $zipId): void {
                app(DownloadSlots::class)->release($subject);
                Cache::put(DownloadedFile::failureKey($zipId), 'failed', now()->addMinutes(10));
            })
            ->onConnection($connection)
            ->onQueue($queue)
            ->dispatch();

        $zip->update(['batch_id' => $batch->id]);

        return $zip->refresh();
    }

    private function connection(): string
    {
        return config('queue.default') === 'sync' ? 'sync' : 'deferred';
    }
}
