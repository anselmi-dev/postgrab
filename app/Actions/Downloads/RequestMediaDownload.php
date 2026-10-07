<?php

namespace App\Actions\Downloads;

use App\Data\MediaItem;
use App\Data\TweetData;
use App\Jobs\DownloadMediaJob;
use App\Models\DownloadedFile;
use App\Models\UsageEvent;
use App\Settings\DownloaderSettings;
use App\Support\DownloadName;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestMediaDownload
{
    public function __construct(
        private EnsureDownloadAllowed $limits,
        private DownloadSlots $slots,
        private DownloaderSettings $settings,
    ) {}

    public function handle(Request $request, TweetData $tweet, MediaItem $media, ?string $quality): DownloadedFile
    {
        $this->limits->handle($request, 1);
        $variant = $media->variant($quality ?? '') ?? $media->bestVariant();

        if (! $variant) {
            throw new \RuntimeException('This media has no downloadable file.');
        }

        $key = $media->type.':'.$media->id.':'.$variant->key;
        $existing = $this->reusable($tweet->id, $key);

        $this->record($request, $tweet->id);

        if ($existing?->isReady()) {
            return $existing;
        }

        $file = $existing ?? DownloadedFile::query()->create([
            'uuid' => (string) Str::uuid(),
            'tweet_id' => $tweet->id,
            'variant' => $key,
            'disk' => 'downloads',
            'extension' => $variant->extension,
            'download_name' => DownloadName::file($tweet->authorHandle, $tweet->id, $media->index, $variant->extension),
            'expires_at' => now()->addHours($this->settings->file_ttl_hours),
        ]);

        $subject = $this->limits->subject($request);
        $queue = $request->user() ? 'downloads-high' : 'downloads';
        $this->slots->acquire($subject, $request->user() !== null);

        DownloadMediaJob::dispatch($variant->url, $file->id, $subject)->onQueue($queue);

        return $file;
    }

    private function reusable(string $tweetId, string $variant): ?DownloadedFile
    {
        return DownloadedFile::query()
            ->where('tweet_id', $tweetId)
            ->where('variant', $variant)
            ->where('expires_at', '>', now())
            ->first();
    }

    private function record(Request $request, string $tweetId): void
    {
        UsageEvent::query()->create([
            'type' => 'download',
            'tweet_id' => $tweetId,
            'success' => true,
            'user_id' => $request->user()?->id,
            'units' => 1,
        ]);
    }
}
