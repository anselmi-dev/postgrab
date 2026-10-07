<?php

use App\Support\TweetIdExtractor;

it('extracts a status id from public x links', function (string $url, ?string $id) {
    expect(TweetIdExtractor::extract($url))->toBe($id);
})->with([
    ['https://x.com/jack/status/20', '20'],
    ['https://twitter.com/jack/status/20?s=20', '20'],
    ['https://mobile.twitter.com/jack/status/20', '20'],
    ['https://fxtwitter.com/jack/status/20', '20'],
    ['https://vxtwitter.com/jack/status/20', '20'],
    ['https://x.com/i/web/status/20', '20'],
    ['https://example.com/status/20', null],
    ['not a url', null],
]);
