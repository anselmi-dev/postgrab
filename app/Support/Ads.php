<?php

namespace App\Support;

use App\Settings\DownloaderSettings;

class Ads
{
    public static function html(string $placement): ?string
    {
        $settings = app(DownloaderSettings::class);
        $fromSettings = match ($placement) {
            'top' => $settings->ad_top,
            'middle' => $settings->ad_middle,
            'footer' => $settings->ad_footer,
            default => '',
        };

        $html = filled($fromSettings) ? $fromSettings : config('downloader.ad_'.$placement);

        return filled($html) ? (string) $html : null;
    }
}
