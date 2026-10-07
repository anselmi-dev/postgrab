<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('downloader.guest_lookups_per_minute', 10);
        $this->migrator->add('downloader.user_lookups_per_minute', 30);
        $this->migrator->add('downloader.guest_downloads_per_hour', 5);
        $this->migrator->add('downloader.user_downloads_per_hour', 30);
        $this->migrator->add('downloader.guest_downloads_per_day', 20);
        $this->migrator->add('downloader.user_downloads_per_day', 100);
        $this->migrator->add('downloader.guest_concurrent', 1);
        $this->migrator->add('downloader.user_concurrent', 3);
        $this->migrator->add('downloader.file_ttl_hours', 24);
        $this->migrator->add('downloader.drivers', 'fxtwitter,syndication');
        $this->migrator->add('downloader.ad_top', '');
        $this->migrator->add('downloader.ad_middle', '');
        $this->migrator->add('downloader.ad_footer', '');
    }
};
