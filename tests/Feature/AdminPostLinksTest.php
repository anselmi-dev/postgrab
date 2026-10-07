<?php

use App\Filament\Resources\DownloadedFiles\DownloadedFileResource;
use App\Filament\Resources\LinkRequests\Pages\ManageLinkRequests;
use App\Filament\Widgets\TopTweets;
use App\Models\DownloadedFile;
use App\Models\LinkRequest;
use App\Models\UsageEvent;
use App\Models\User;
use App\Support\PostPreview;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function admin(): User
{
    $user = User::factory()->create();
    $user->is_admin = true;
    $user->save();

    return $user;
}

it('keeps only https post links', function () {
    expect(PostPreview::url('javascript:alert(1)', '20'))->toBe('https://x.com/i/web/status/20')
        ->and(PostPreview::url('https://x.com/jack/status/20', '20'))->toBe('https://x.com/jack/status/20')
        ->and(PostPreview::image('http://pbs.twimg.com/media/x.jpg'))->toBeNull()
        ->and(PostPreview::image('https://pbs.twimg.com/media/x.jpg'))->toBe('https://pbs.twimg.com/media/x.jpg');
});

it('shows the post link and thumbnail in solicitudes', function () {
    $this->actingAs(admin());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $request = LinkRequest::query()->create([
        'user_id' => User::factory()->create()->id,
        'tweet_id' => '20',
        'url' => 'https://x.com/jack/status/20',
        'author_handle' => 'jack',
        'text_excerpt' => 'hello',
        'thumbnail_url' => 'https://pbs.twimg.com/media/x.jpg',
    ]);

    Livewire\Livewire::test(ManageLinkRequests::class)
        ->assertCanSeeTableRecords([$request])
        ->assertSee('https://x.com/jack/status/20')
        ->assertSee('https://pbs.twimg.com/media/x.jpg');
});

it('shows the post link and thumbnail on the most requested posts', function () {
    $this->actingAs(admin());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $user = User::factory()->create();

    LinkRequest::query()->create([
        'user_id' => $user->id,
        'tweet_id' => '20',
        'url' => 'https://x.com/jack/status/20',
        'author_handle' => 'jack',
        'text_excerpt' => 'hello',
        'thumbnail_url' => 'https://pbs.twimg.com/media/x.jpg',
    ]);

    UsageEvent::query()->create([
        'type' => 'lookup',
        'tweet_id' => '20',
        'success' => true,
        'user_id' => $user->id,
    ]);

    Livewire\Livewire::test(TopTweets::class)
        ->assertSee('https://x.com/jack/status/20')
        ->assertSee('https://pbs.twimg.com/media/x.jpg');
});

it('attaches the stored post link to downloaded files', function () {
    $user = User::factory()->create();

    LinkRequest::query()->create([
        'user_id' => $user->id,
        'tweet_id' => '20',
        'url' => 'https://x.com/jack/status/20',
        'author_handle' => 'jack',
        'text_excerpt' => 'hello',
        'thumbnail_url' => 'https://pbs.twimg.com/media/x.jpg',
    ]);

    $file = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '20',
        'variant' => 'photo:1:orig',
        'disk' => 'downloads',
        'extension' => 'mp4',
        'download_name' => 'jack_20_1.mp4',
        'expires_at' => now()->addHour(),
    ]);

    $loaded = DownloadedFileResource::getEloquentQuery()->findOrFail($file->id);

    expect($loaded->getAttribute('post_url'))->toBe('https://x.com/jack/status/20')
        ->and($loaded->getAttribute('preview_thumb'))->toBe('https://pbs.twimg.com/media/x.jpg')
        ->and(PostPreview::file($loaded))->toBe('https://pbs.twimg.com/media/x.jpg');
});

it('previews a stored image for admins when the post has no thumbnail', function () {
    Storage::fake('downloads');

    $file = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '21',
        'variant' => 'photo:1:orig',
        'disk' => 'downloads',
        'path' => 'files/preview.jpg',
        'extension' => 'jpg',
        'download_name' => 'post_21_1.jpg',
        'expires_at' => now()->addHour(),
        'ready_at' => now(),
    ]);

    Storage::disk('downloads')->put($file->path, 'image-bytes');

    $loaded = DownloadedFileResource::getEloquentQuery()->findOrFail($file->id);

    $this->actingAs(admin())
        ->get(PostPreview::file($loaded))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');

    $video = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '22',
        'variant' => 'video:1:720',
        'disk' => 'downloads',
        'path' => 'files/clip.mp4',
        'extension' => 'mp4',
        'download_name' => 'post_22_1.mp4',
        'expires_at' => now()->addHour(),
        'ready_at' => now(),
    ]);

    Storage::disk('downloads')->put($video->path, 'video-bytes');

    $this->get(Filament::getPanel('admin')->route('files.preview', ['file' => $video]))
        ->assertNotFound();
});

it('hides file previews from guests and non admins', function () {
    Storage::fake('downloads');

    $file = DownloadedFile::query()->create([
        'uuid' => (string) Str::uuid(),
        'tweet_id' => '23',
        'variant' => 'photo:1:orig',
        'disk' => 'downloads',
        'path' => 'files/hidden.jpg',
        'extension' => 'jpg',
        'download_name' => 'post_23_1.jpg',
        'expires_at' => now()->addHour(),
        'ready_at' => now(),
    ]);

    Storage::disk('downloads')->put($file->path, 'image-bytes');

    $url = Filament::getPanel('admin')->route('files.preview', ['file' => $file]);

    $this->get($url)->assertRedirect();

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertForbidden();
});
