<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

class DownloadedFile extends Model
{
    use Prunable;

    protected $fillable = [
        'uuid',
        'tweet_id',
        'variant',
        'disk',
        'path',
        'extension',
        'size_bytes',
        'download_name',
        'expires_at',
        'ready_at',
        'batch_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'ready_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<=', now());
    }

    protected function pruning(): void
    {
        if ($this->path) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }

    public function isReady(): bool
    {
        return $this->ready_at !== null && $this->expires_at->isFuture() && filled($this->path);
    }

    public function isPreviewableImage(): bool
    {
        if (! $this->isReady() || blank($this->path)) {
            return false;
        }

        return in_array(strtolower((string) $this->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
