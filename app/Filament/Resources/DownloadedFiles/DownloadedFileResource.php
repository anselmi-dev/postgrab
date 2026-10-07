<?php

namespace App\Filament\Resources\DownloadedFiles;

use App\Filament\Resources\DownloadedFiles\Pages\ManageDownloadedFiles;
use App\Models\BlockedTweet;
use App\Models\DownloadedFile;
use App\Support\Bytes;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DownloadedFileResource extends Resource
{
    protected static ?string $model = DownloadedFile::class;

    protected static ?string $navigationLabel = 'Archivos';

    protected static ?string $modelLabel = 'archivo';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tweet_id')->label('Post')->searchable(),
                TextColumn::make('variant')->label('Variante'),
                TextColumn::make('size_bytes')->label('Peso')->formatStateUsing(fn ($state) => Bytes::human($state) ?? '—'),
                TextColumn::make('expires_at')->label('Vence')->dateTime()->sortable(),
                TextColumn::make('ready_at')->label('Listo')->dateTime()->placeholder('pendiente'),
            ])
            ->recordActions([
                Action::make('dmca')
                    ->label('Eliminar por reclamo DMCA')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (DownloadedFile $record): void {
                        BlockedTweet::query()->firstOrCreate(
                            ['tweet_id' => $record->tweet_id],
                            ['reason' => 'DMCA'],
                        );

                        DownloadedFile::query()
                            ->where('tweet_id', $record->tweet_id)
                            ->get()
                            ->each(function (DownloadedFile $file): void {
                                if ($file->path) {
                                    Storage::disk($file->disk)->delete($file->path);
                                }

                                $file->delete();
                            });

                        Cache::forget('tweet:'.$record->tweet_id);
                    }),
            ])
            ->defaultSort('expires_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDownloadedFiles::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
