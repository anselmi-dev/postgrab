<?php

namespace App\Filament\Resources\LinkRequests;

use App\Filament\Resources\LinkRequests\Pages\ManageLinkRequests;
use App\Models\LinkRequest;
use App\Support\PostPreview;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LinkRequestResource extends Resource
{
    protected static ?string $model = LinkRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Actividad';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $recordTitleAttribute = 'author_handle';

    protected static ?string $modelLabel = 'solicitud';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label('Vista previa')
                    ->state(fn (LinkRequest $record): ?string => PostPreview::image($record->thumbnail_url))
                    ->checkFileExistence(false)
                    ->imageHeight(48)
                    ->imageWidth(72)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->placeholder('—'),
                TextColumn::make('user.email')->label('Usuario')->searchable(),
                TextColumn::make('author_handle')->label('Autor')->searchable(),
                TextColumn::make('url')
                    ->label('Enlace')
                    ->state(fn (LinkRequest $record): string => PostPreview::url($record->url, $record->tweet_id))
                    ->url(fn (string $state): string => $state, true)
                    ->limit(42)
                    ->tooltip(fn (string $state): string => $state)
                    ->searchable(),
                TextColumn::make('tweet_id')->label('Post')->searchable(),
                TextColumn::make('text_excerpt')->label('Texto')->limit(60),
                TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLinkRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
