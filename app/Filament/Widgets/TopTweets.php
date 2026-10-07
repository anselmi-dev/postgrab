<?php

namespace App\Filament\Widgets;

use App\Models\UsageEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopTweets extends TableWidget
{
    protected static ?string $heading = 'Posts más pedidos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->columns([
                TextColumn::make('tweet_id')->label('Post'),
                TextColumn::make('total')->label('Consultas'),
            ])
            ->defaultKeySort(false)
            ->defaultSort('total', 'desc')
            ->paginated(false);
    }

    private function query(): Builder
    {
        return UsageEvent::query()
            ->selectRaw('min(id) as id, tweet_id, count(*) as total')
            ->where('type', 'lookup')
            ->where('success', true)
            ->whereNotNull('tweet_id')
            ->groupBy('tweet_id')
            ->limit(8);
    }
}
