<?php

namespace App\Filament\Resources\DownloadedFiles;

use App\Filament\Resources\DownloadedFiles\Pages\ManageDownloadedFiles;
use App\Models\BlockedTweet;
use App\Models\DownloadedFile;
use App\Models\LinkRequest;
use App\Support\Bytes;
use App\Support\PostPreview;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DownloadedFileResource extends Resource
{
    protected static ?string $model = DownloadedFile::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Actividad';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Archivos';

    protected static ?string $recordTitleAttribute = 'tweet_id';

    protected static ?string $modelLabel = 'archivo';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->addSelect([
            'post_url' => LinkRequest::latestValueForTweet('url', 'downloaded_files.tweet_id'),
            'preview_thumb' => LinkRequest::latestValueForTweet('thumbnail_url', 'downloaded_files.tweet_id', filledOnly: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('Vista previa')
                    ->state(fn (DownloadedFile $record): ?string => PostPreview::file($record))
                    ->checkFileExistence(false)
                    ->imageHeight(48)
                    ->imageWidth(72)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->placeholder('—'),
                TextColumn::make('post_url')
                    ->label('Enlace')
                    ->state(fn (DownloadedFile $record): string => PostPreview::url($record->getAttribute('post_url'), $record->tweet_id))
                    ->url(fn (string $state): string => $state, true)
                    ->limit(42)
                    ->tooltip(fn (string $state): string => $state),
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
