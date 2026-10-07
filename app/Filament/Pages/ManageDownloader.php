<?php

namespace App\Filament\Pages;

use App\Settings\DownloaderSettings;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageDownloader extends SettingsPage
{
    protected static string $settings = DownloaderSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Ajustes';

    protected static ?string $title = 'Ajustes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('guest_lookups_per_minute')->label('Consultas/min invitado')->numeric()->required(),
            TextInput::make('user_lookups_per_minute')->label('Consultas/min usuario')->numeric()->required(),
            TextInput::make('guest_downloads_per_hour')->label('Descargas/hora invitado')->numeric()->required(),
            TextInput::make('user_downloads_per_hour')->label('Descargas/hora usuario')->numeric()->required(),
            TextInput::make('guest_downloads_per_day')->label('Descargas/día invitado')->numeric()->required(),
            TextInput::make('user_downloads_per_day')->label('Descargas/día usuario')->numeric()->required(),
            TextInput::make('guest_concurrent')->label('Simultáneas invitado')->numeric()->required(),
            TextInput::make('user_concurrent')->label('Simultáneas usuario')->numeric()->required(),
            TextInput::make('file_ttl_hours')->label('Horas de vida del archivo')->numeric()->required(),
            TextInput::make('drivers')->label('Drivers, en orden')->required()->columnSpanFull(),
            Textarea::make('ad_top')->label('Anuncio superior')->rows(4)->columnSpanFull(),
            Textarea::make('ad_middle')->label('Anuncio central')->rows(4)->columnSpanFull(),
            Textarea::make('ad_footer')->label('Anuncio inferior')->rows(4)->columnSpanFull(),
        ]);
    }
}
