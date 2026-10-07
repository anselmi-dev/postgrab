<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedTweet extends Model
{
    protected $fillable = [
        'tweet_id',
        'reason',
    ];
}
