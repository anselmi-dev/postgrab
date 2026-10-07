<?php

namespace App\Filament\Resources\DownloadedFiles\Pages;

use App\Filament\Resources\DownloadedFiles\DownloadedFileResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDownloadedFiles extends ManageRecords
{
    protected static string $resource = DownloadedFileResource::class;
}
