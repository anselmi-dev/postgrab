<?php

namespace App\Filament\Widgets;

use App\Models\LinkRequest;
use App\Models\UsageEvent;
use App\Support\PostPreview;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopTweets extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Posts más pedidos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->query())
            ->columns([
                ImageColumn::make('preview')
                    ->label('Vista previa')
                    ->state(fn (UsageEvent $record): ?string => PostPreview::image($record->getAttribute('preview_thumb')))
                    ->checkFileExistence(false)
                    ->imageHeight(48)
                    ->imageWidth(72)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->placeholder('—'),
                TextColumn::make('post_url')
                    ->label('Enlace')
                    ->state(fn (UsageEvent $record): string => PostPreview::url($record->getAttribute('post_url'), (string) $record->tweet_id))
                    ->url(fn (string $state): string => $state, true)
                    ->limit(42)
                    ->tooltip(fn (string $state): string => $state),
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
            ->selectRaw('min(usage_events.id) as id, usage_events.tweet_id, count(*) as total')
            ->selectSub(LinkRequest::latestValueForTweet('url', 'usage_events.tweet_id'), 'post_url')
            ->selectSub(LinkRequest::latestValueForTweet('thumbnail_url', 'usage_events.tweet_id', filledOnly: true), 'preview_thumb')
            ->where('type', 'lookup')
            ->where('success', true)
            ->whereNotNull('usage_events.tweet_id')
            ->groupBy('usage_events.tweet_id')
            ->limit(8);
    }
}
