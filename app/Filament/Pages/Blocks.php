<?php

namespace App\Filament\Pages;

use App\Models\DownloadBlock;
use App\Models\IpBan;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class Blocks extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $navigationLabel = 'Bloqueos';

    protected static ?string $title = 'Bloqueos';

    protected string $view = 'filament.pages.blocks';

    public string $ip = '';

    public string $reason = '';

    public function lift(int $id): void
    {
        $block = DownloadBlock::query()->find($id);

        if (! $block) {
            return;
        }

        $key = str_starts_with($block->subject, 'user:')
            ? 'dl:'.substr($block->subject, 5)
            : 'dl:'.substr($block->subject, 3);

        Cache::forget("{$key}:lock");
        Cache::forget("{$key}:strikes");
        $block->delete();
    }

    public function ban(): void
    {
        $this->validate(['ip' => ['required', 'ip']]);

        IpBan::query()->updateOrCreate(
            ['ip' => $this->ip],
            ['reason' => $this->reason ?: null, 'expires_at' => null],
        );

        $this->reset(['ip', 'reason']);
    }

    public function unban(int $id): void
    {
        IpBan::query()->whereKey($id)->delete();
    }

    protected function getViewData(): array
    {
        return [
            'blocks' => DownloadBlock::query()->latest('updated_at')->get(),
            'bans' => IpBan::query()->latest()->get(),
        ];
    }
}
