<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageEvent extends Model
{
    protected $fillable = [
        'type',
        'tweet_id',
        'driver',
        'success',
        'user_id',
        'units',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'units' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
