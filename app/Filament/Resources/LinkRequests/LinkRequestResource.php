<?php

namespace App\Filament\Resources\LinkRequests;

use App\Filament\Resources\LinkRequests\Pages\ManageLinkRequests;
use App\Models\LinkRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LinkRequestResource extends Resource
{
    protected static ?string $model = LinkRequest::class;

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')->label('Usuario')->searchable(),
                TextColumn::make('author_handle')->label('Autor')->searchable(),
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
