<?php

use App\Livewire\History;
use App\Models\LinkRequest;
use App\Models\User;
use Livewire\Livewire;

it('filters history by author, text, or post id', function () {
    $user = User::factory()->create();

    LinkRequest::query()->create([
        'user_id' => $user->id,
        'tweet_id' => '111',
        'url' => 'https://x.com/NASA/status/111',
        'author_handle' => 'NASA',
        'text_excerpt' => 'Artemis update',
    ]);

    LinkRequest::query()->create([
        'user_id' => $user->id,
        'tweet_id' => '222',
        'url' => 'https://x.com/esa/status/222',
        'author_handle' => 'esa',
        'text_excerpt' => 'A different launch',
    ]);

    Livewire::actingAs($user)
        ->test(History::class)
        ->assertSee('@NASA')
        ->assertSee('@esa')
        ->set('search', 'Artemis')
        ->assertSee('@NASA')
        ->assertDontSee('@esa')
        ->set('search', '222')
        ->assertSee('@esa')
        ->assertDontSee('@NASA')
        ->set('search', 'nada')
        ->assertSee(__('app.history_no_results'));
});
