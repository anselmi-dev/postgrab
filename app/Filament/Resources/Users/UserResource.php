<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $modelLabel = 'usuario';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable(),
                TextColumn::make('email')->searchable(),
                IconColumn::make('is_admin')->label('Admin')->boolean(),
                TextColumn::make('suspended_at')->label('Suspendido')->dateTime()->placeholder('—'),
                TextColumn::make('link_requests_count')->counts('linkRequests')->label('Historial'),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspender')
                    ->visible(fn (User $record): bool => $record->suspended_at === null && ! $record->is_admin)
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->suspended_at = now();
                        $record->save();
                    }),
                Action::make('unsuspend')
                    ->label('Reactivar')
                    ->visible(fn (User $record): bool => $record->suspended_at !== null)
                    ->action(function (User $record): void {
                        $record->suspended_at = null;
                        $record->save();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
