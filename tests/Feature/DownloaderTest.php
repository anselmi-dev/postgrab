<?php

use App\Actions\Downloads\EnsureDownloadAllowed;
use App\Actions\Downloads\RequestMediaDownload;
use App\Exceptions\InvalidTweetUrlException;
use App\Exceptions\TooManyDownloadsException;
use App\Exceptions\TweetBlockedException;
use App\Exceptions\TweetHasNoMediaException;
use App\Exceptions\TweetNotFoundException;
use App\Exceptions\UnsafeMediaUrlException;
use App\Jobs\DownloadMediaJob;
use App\Models\BlockedTweet;
use App\Models\DownloadedFile;
use App\Services\TweetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

function videoPayload(): array
{
    return [
        'status' => [
            'id' => '2107850292305818006',
            'url' => 'https://x.com/NASA/status/2107850292305818006',
            'text' => 'Artemis',
            'author' => ['name' => 'NASA', 'screen_name' => 'NASA'],
            'created_at' => 'Wed Oct 07 12:00:00 +0000 2026',
            'media' => [
                'all' => [[
                    'id' => '1',
                    'type' => 'video',
                    'thumbnail_url' => 'https://pbs.twimg.com/media/x.jpg',
                    'formats' => [
                        ['url' => 'https://video.twimg.com/amplify_video/1/pl/a.m3u8', 'container' => 'm3u8'],
                        ['url' => 'https://video.twimg.com/amplify_video/1/vid/avc1/320x568/a.mp4', 'bitrate' => 632000, 'container' => 'mp4'],
                        ['url' => 'https://video.twimg.com/amplify_video/1/vid/avc1/1080x1920/a.mp4', 'bitrate' => 10368000, 'container' => 'mp4'],
                    ],
                ]],
            ],
        ],
    ];
}

it('normalizes a fxtwitter video and keeps mp4 qualities', function () {
    Http::fake([
        'api.fxtwitter.com/*' => Http::response(videoPayload()),
    ]);

    $tweet = app(TweetService::class)->lookup('https://x.com/NASA/status/2107850292305818006', Request::create('/'));

    expect($tweet->authorHandle)->toBe('NASA')
        ->and($tweet->media)->toHaveCount(1)
        ->and($tweet->media[0]->type)->toBe('video')
        ->and(collect($tweet->media[0]->variants)->pluck('key')->all())->toBe(['320x568', '1080x1920'])
        ->and($tweet->media[0]->bestVariant()?->key)->toBe('1080x1920');
});

it('rejects an invalid link', function () {
    expect(fn () => app(TweetService::class)->lookup('https://example.com/nope', Request::create('/')))
        ->toThrow(InvalidTweetUrlException::class);
});

it('rejects a missing post', function () {
    Http::fake([
        'api.fxtwitter.com/*' => Http::response(['code' => 404], 404),
        'cdn.syndication.twimg.com/*' => Http::response(['tombstone' => ['reason' => 'deleted']], 200),
    ]);

    expect(fn () => app(TweetService::class)->lookup('https://x.com/jack/status/20', Request::create('/')))
        ->toThrow(TweetNotFoundException::class);
});

it('rejects a post without media', function () {
    Http::fake([
        'api.fxtwitter.com/*' => Http::response([
            'status' => [
                'id' => '20',
                'url' => 'https://x.com/jack/status/20',
                'text' => 'just setting up my twttr',
                'author' => ['name' => 'jack', 'screen_name' => 'jack'],
                'media' => ['all' => []],
            ],
        ]),
    ]);

    expect(fn () => app(TweetService::class)->lookup('https://x.com/jack/status/20', Request::create('/')))
        ->toThrow(TweetHasNoMediaException::class);
});

it('rejects a blocked post', function () {
    BlockedTweet::query()->create(['tweet_id' => '99', 'reason' => 'DMCA']);

    expect(fn () => app(TweetService::class)->lookup('https://x.com/a/status/99', Request::create('/')))
        ->toThrow(TweetBlockedException::class);
});

it('blocks downloads after the guest hourly limit', function () {
    $request = Request::create('/');
    $limits = app(EnsureDownloadAllowed::class);

    foreach (range(1, 5) as $ignored) {
        $limits->handle($request);
    }

    expect(fn () => $limits->handle($request))->toThrow(TooManyDownloadsException::class);
    expect($limits->remaining($request))->toBe(0);
});

it('serves a ready file only through a signed url', function () {
    Storage::fake('downloads');

    $file = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '20',
        'variant' => 'photo:1:original',
        'disk' => 'downloads',
        'path' => 'files/one.jpg',
        'extension' => 'jpg',
        'size_bytes' => 5,
        'download_name' => 'jack_20_1.jpg',
        'expires_at' => now()->addHour(),
        'ready_at' => now(),
    ]);

    Storage::disk('downloads')->put($file->path, 'image');

    $signed = URL::temporarySignedRoute('download.show', now()->addMinutes(10), ['file' => $file]);

    $this->get($signed)
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get(route('download.show', $file))->assertForbidden();
});

it('prunes expired downloads and deletes the file', function () {
    Storage::fake('downloads');

    $file = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '20',
        'variant' => 'photo:1:original',
        'disk' => 'downloads',
        'path' => 'files/old.jpg',
        'extension' => 'jpg',
        'size_bytes' => 4,
        'download_name' => 'jack_20_1.jpg',
        'expires_at' => now()->subHour(),
        'ready_at' => now()->subDay(),
    ]);

    Storage::disk('downloads')->put($file->path, 'old');

    $this->artisan('model:prune', ['--model' => [DownloadedFile::class]])->assertSuccessful();

    Storage::disk('downloads')->assertMissing('files/old.jpg');
    expect(DownloadedFile::query()->count())->toBe(0);
});

it('downloads a photo from twimg and refuses other hosts', function () {
    Storage::fake('downloads');
    Http::fake([
        'https://pbs.twimg.com/*' => Http::response('image-bytes', 200, ['Content-Length' => '11']),
    ]);

    $file = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '20',
        'variant' => 'photo:1:original',
        'disk' => 'downloads',
        'extension' => 'jpg',
        'download_name' => 'jack_20_1.jpg',
        'expires_at' => now()->addDay(),
    ]);

    (new DownloadMediaJob('https://pbs.twimg.com/media/x.jpg', $file->id))->handle();

    $file->refresh();
    expect($file->isReady())->toBeTrue()
        ->and(Storage::disk('downloads')->get($file->path))->toBe('image-bytes');

    $pending = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '21',
        'variant' => 'photo:2:original',
        'disk' => 'downloads',
        'extension' => 'jpg',
        'download_name' => 'jack_21_1.jpg',
        'expires_at' => now()->addDay(),
    ]);

    expect(fn () => (new DownloadMediaJob('http://127.0.0.1/secret', $pending->id))->handle())
        ->toThrow(UnsafeMediaUrlException::class);
});

it('shows the public page', function () {
    $this->get('/es')
        ->assertOk()
        ->assertSee('Descargá videos de Twitter')
        ->assertSee('name="description"', false)
        ->assertSee(__('app.seo_description', [], 'es'), false)
        ->assertSee('application/ld+json', false);
});

it('reuses a cached file when the same photo is requested again', function () {
    Storage::fake('downloads');
    Http::fake([
        'api.fxtwitter.com/*' => Http::response([
            'status' => [
                'id' => '20',
                'url' => 'https://x.com/jack/status/20',
                'text' => 'photo',
                'author' => ['name' => 'jack', 'screen_name' => 'jack'],
                'media' => ['all' => [[
                    'id' => '9',
                    'type' => 'photo',
                    'url' => 'https://pbs.twimg.com/media/x.jpg',
                    'thumbnail_url' => 'https://pbs.twimg.com/media/x.jpg',
                ]]],
            ],
        ]),
        'https://pbs.twimg.com/*' => Http::response('image-bytes', 200, ['Content-Length' => '11']),
    ]);

    $tweet = app(TweetService::class)->lookup('https://x.com/jack/status/20', Request::create('/'));
    $file = app(RequestMediaDownload::class)->handle(Request::create('/'), $tweet, $tweet->media[0], null);

    expect($file->fresh()->isReady())->toBeTrue();
    expect(DownloadedFile::query()->count())->toBe(1);
});
