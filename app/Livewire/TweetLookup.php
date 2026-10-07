<?php

namespace App\Livewire;

use App\Actions\Downloads\EnsureDownloadAllowed;
use App\Actions\Downloads\RequestMediaDownload;
use App\Actions\Downloads\RequestZipDownload;
use App\Data\TweetData;
use App\Exceptions\DownloadTooLargeException;
use App\Exceptions\InvalidTweetUrlException;
use App\Exceptions\TooManyDownloadsException;
use App\Exceptions\TooManyLookupsException;
use App\Exceptions\TweetBlockedException;
use App\Exceptions\TweetHasNoMediaException;
use App\Exceptions\TweetNotFoundException;
use App\Exceptions\TweetUnavailableException;
use App\Models\DownloadedFile;
use App\Models\LinkRequest;
use App\Services\TweetService;
use App\Support\Bytes;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TweetLookup extends Component
{
    public string $url = '';

    public string $turnstileToken = '';

    #[Locked]
    public ?array $tweet = null;

    public array $selected = [];

    public array $qualities = [];

    public ?string $error = null;

    public bool $challenge = false;

    #[Locked]
    public ?int $pendingFileId = null;

    public ?string $downloadUrl = null;

    public ?string $progress = null;

    public function mount(): void
    {
        $url = request()->query('url');

        if (is_string($url) && $url !== '') {
            $this->url = $url;
            $this->lookup();
        }
    }

    public function lookup(): void
    {
        $this->validate(['url' => ['required', 'string', 'max:500']]);
        $this->reset(['error', 'challenge', 'downloadUrl', 'pendingFileId', 'progress', 'selected']);

        try {
            $data = app(TweetService::class)->lookup($this->url, request(), $this->turnstileToken);
        } catch (InvalidTweetUrlException) {
            $this->fail(__('app.error_invalid'));

            return;
        } catch (TweetNotFoundException) {
            $this->fail(__('app.error_missing'));

            return;
        } catch (TweetHasNoMediaException) {
            $this->fail(__('app.error_no_media'));

            return;
        } catch (TweetBlockedException) {
            $this->fail(__('app.error_blocked'));

            return;
        } catch (TweetUnavailableException) {
            $this->fail(__('app.error_unavailable'));

            return;
        } catch (TooManyLookupsException $exception) {
            $this->challenge = $exception->challenge;
            $this->fail($exception->challenge ? __('app.error_lookup_challenge') : __('app.error_lookup_limit'));

            return;
        }

        $this->tweet = $data->toArray();
        $this->qualities = [];

        foreach ($data->media as $media) {
            $best = $media->bestVariant();

            if ($best) {
                $this->qualities[$media->id] = $best->key;
            }
        }

        $user = auth()->user();

        if ($user?->hasVerifiedEmail()) {
            LinkRequest::query()->updateOrCreate(
                ['user_id' => $user->id, 'tweet_id' => $data->id],
                [
                    'url' => $data->url,
                    'author_handle' => $data->authorHandle,
                    'text_excerpt' => mb_substr($data->text, 0, 280),
                    'thumbnail_url' => $data->media[0]->thumbnailUrl,
                ],
            );
        }
    }

    public function download(string $mediaId): void
    {
        $tweet = $this->tweetData();
        $media = $tweet?->mediaById($mediaId);

        if (! $tweet || ! $media) {
            return;
        }

        $this->start(function () use ($tweet, $media) {
            return app(RequestMediaDownload::class)->handle(request(), $tweet, $media, $this->qualities[$media->id] ?? null);
        });
    }

    public function downloadSelected(): void
    {
        $this->downloadZip($this->selected);
    }

    public function downloadAll(): void
    {
        $ids = array_map(fn (array $media) => $media['id'], $this->tweet['media'] ?? []);
        $this->downloadZip($ids);
    }

    public function refreshDownload(): void
    {
        if (! $this->pendingFileId || ! $this->tweet) {
            return;
        }

        $file = DownloadedFile::query()->find($this->pendingFileId);

        if (! $file || $file->tweet_id !== $this->tweet['id']) {
            $this->pendingFileId = null;

            return;
        }

        if ($file->batch_id) {
            $batch = Bus::findBatch($file->batch_id);

            if ($batch) {
                $this->progress = __('app.preparing', [
                    'done' => $batch->processedJobs(),
                    'total' => $batch->totalJobs,
                ]);
            }
        }

        if ($file->isReady()) {
            $this->downloadUrl = URL::temporarySignedRoute('download.show', now()->addMinutes(10), ['file' => $file]);
            $this->pendingFileId = null;
            $this->progress = null;
        }
    }

    public function render()
    {
        $limits = app(EnsureDownloadAllowed::class);

        return view('livewire.tweet-lookup', [
            'remaining' => $limits->remaining(request()),
            'lockedUntil' => $limits->lockUntil(request()),
            'bytes' => Bytes::class,
        ]);
    }

    /**
     * @param  list<string>  $ids
     */
    private function downloadZip(array $ids): void
    {
        $tweet = $this->tweetData();

        if (! $tweet || count($tweet->media) < 2) {
            return;
        }

        $selection = [];

        foreach ($ids as $id) {
            $selection[] = ['id' => (string) $id, 'quality' => $this->qualities[$id] ?? null];
        }

        $this->start(fn () => app(RequestZipDownload::class)->handle(request(), $tweet, $selection));
    }

    private function start(callable $callback): void
    {
        $this->reset(['error', 'downloadUrl', 'progress']);

        try {
            $file = $callback();
        } catch (TooManyDownloadsException $exception) {
            $this->error = $exception->reason === 'concurrent'
                ? __('app.error_concurrent')
                : __('app.error_download_limit', ['time' => $exception->until->locale(app()->getLocale())->isoFormat('HH:mm')]);

            return;
        } catch (DownloadTooLargeException) {
            $this->error = __('app.error_too_large');

            return;
        }

        if ($file->isReady()) {
            $this->downloadUrl = URL::temporarySignedRoute('download.show', now()->addMinutes(10), ['file' => $file]);

            return;
        }

        $this->pendingFileId = $file->id;
        $this->progress = __('app.preparing_generic');
    }

    private function tweetData(): ?TweetData
    {
        return is_array($this->tweet) ? TweetData::fromArray($this->tweet) : null;
    }

    private function fail(string $message): void
    {
        $this->tweet = null;
        $this->error = $message;
    }
}
