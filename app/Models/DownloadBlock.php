<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadBlock extends Model
{
    protected $fillable = [
        'subject',
        'strikes',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'strikes' => 'integer',
            'locked_until' => 'datetime',
        ];
    }
}
