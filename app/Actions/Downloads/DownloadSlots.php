<?php

namespace App\Actions\Downloads;

use App\Exceptions\TooManyDownloadsException;
use App\Settings\DownloaderSettings;
use Illuminate\Support\Facades\Cache;

class DownloadSlots
{
    public function __construct(private DownloaderSettings $settings) {}

    public function acquire(string $subject, bool $authenticated): void
    {
        $max = $authenticated ? $this->settings->user_concurrent : $this->settings->guest_concurrent;
        $active = (int) Cache::get($this->key($subject), 0);

        if ($active >= $max) {
            throw new TooManyDownloadsException(now()->addMinute(), 'concurrent');
        }

        Cache::put($this->key($subject), $active + 1, now()->addMinutes(5));
    }

    public function release(string $subject): void
    {
        $active = max(0, (int) Cache::get($this->key($subject), 0) - 1);
        Cache::put($this->key($subject), $active, now()->addMinutes(5));
    }

    private function key(string $subject): string
    {
        return 'dl-active:'.$subject;
    }
}
