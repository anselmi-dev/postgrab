<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class DownloaderSettings extends Settings
{
    public int $guest_lookups_per_minute;

    public int $user_lookups_per_minute;

    public int $guest_downloads_per_hour;

    public int $user_downloads_per_hour;

    public int $guest_downloads_per_day;

    public int $user_downloads_per_day;

    public int $guest_concurrent;

    public int $user_concurrent;

    public int $file_ttl_hours;

    public string $drivers;

    public string $ad_top;

    public string $ad_middle;

    public string $ad_footer;

    public static function group(): string
    {
        return 'downloader';
    }

    /**
     * @return list<string>
     */
    public function driverNames(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->drivers))));
    }
}
