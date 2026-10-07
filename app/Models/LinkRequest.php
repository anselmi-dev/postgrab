<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @return Builder<LinkRequest>
     */
    public static function latestValueForTweet(string $column, string $tweetColumn, bool $filledOnly = false): Builder
    {
        return static::query()
            ->select($column)
            ->whereColumn('link_requests.tweet_id', $tweetColumn)
            ->when($filledOnly, fn (Builder $query) => $query->whereNotNull($column)->where($column, '!=', ''))
            ->latest('link_requests.id')
            ->limit(1);
    }
}
