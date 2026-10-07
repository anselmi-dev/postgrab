<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkRequest extends Model
{
    protected $fillable = [
        'user_id',
        'tweet_id',
        'url',
        'author_handle',
        'text_excerpt',
        'thumbnail_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
