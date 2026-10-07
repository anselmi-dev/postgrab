<?php

namespace App\Filament\Widgets;

use App\Support\UsageSeries;
use Filament\Widgets\Widget;

class UsageOverview extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.usage-overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'stats' => UsageSeries::overview(),
        ];
    }
}
