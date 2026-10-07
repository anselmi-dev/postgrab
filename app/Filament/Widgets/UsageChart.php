<?php

namespace App\Filament\Widgets;

use App\Support\UsageSeries;
use Filament\Widgets\Widget;

class UsageChart extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.usage-chart';

    public string $range = 'semana';

    public function setRange(string $range): void
    {
        if (! in_array($range, ['semana', 'mes', 'ano'], true)) {
            return;
        }

        $this->range = $range;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'range' => $this->range,
            'chart' => UsageSeries::chart($this->range),
        ];
    }
}
