<?php

use App\Models\DownloadedFile;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', [
    '--model' => [DownloadedFile::class],
])->hourly()->withoutOverlapping();

Schedule::command('downloads:sweep-orphans')->daily();
