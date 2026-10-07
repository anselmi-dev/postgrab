<?php

namespace App\Filament\Widgets;

use App\Models\UsageEvent;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class UsageChart extends ChartWidget
{
    protected ?string $heading = 'Últimos 7 días';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $lookups = [];
        $downloads = [];

        foreach (range(6, 0) as $daysAgo) {
            $day = Carbon::today()->subDays($daysAgo);
            $labels[] = $day->format('d/m');
            $lookups[] = UsageEvent::query()->where('type', 'lookup')->whereDate('created_at', $day)->count();
            $downloads[] = UsageEvent::query()->where('type', 'download')->whereDate('created_at', $day)->sum('units');
        }

        return [
            'datasets' => [
                ['label' => 'Consultas', 'data' => $lookups],
                ['label' => 'Descargas', 'data' => $downloads],
            ],
            'labels' => $labels,
        ];
    }
}
