<?php

namespace App\Contracts;

use App\Data\TweetData;
use App\Exceptions\TweetNotFoundException;

interface TweetProvider
{
    public function name(): string;

    /**
     * @throws TweetNotFoundException
     */
    public function fetch(string $tweetId): ?TweetData;
}
