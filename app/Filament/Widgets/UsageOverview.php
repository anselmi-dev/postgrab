<?php

namespace App\Filament\Widgets;

use App\Models\DownloadedFile;
use App\Models\UsageEvent;
use App\Support\Bytes;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsageOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $lookups = UsageEvent::query()->where('type', 'lookup')->whereDate('created_at', today());
        $downloads = UsageEvent::query()->where('type', 'download')->whereDate('created_at', today());
        $driver = UsageEvent::query()->where('type', 'lookup')->where('driver', 'fxtwitter')->where('created_at', '>=', now()->subDay());
        $driverTotal = (clone $driver)->count();
        $driverOk = (clone $driver)->where('success', true)->count();
        $disk = (int) DownloadedFile::query()->whereNotNull('ready_at')->where('expires_at', '>', now())->sum('size_bytes');

        return [
            Stat::make('Consultas hoy', (clone $lookups)->count()),
            Stat::make('Descargas hoy', (clone $downloads)->sum('units')),
            Stat::make('Éxito FxTwitter 24 h', $driverTotal === 0 ? '—' : round(($driverOk / $driverTotal) * 100).'%'),
            Stat::make('Disco en uso', Bytes::human($disk) ?? '0 B'),
        ];
    }
}
