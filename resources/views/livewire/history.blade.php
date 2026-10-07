<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.history') }}</h2>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="clear" wire:confirm="{{ __('app.clear_confirm') }}" class="rounded-xl border border-neutral-200 px-4 py-2 text-sm">{{ __('app.clear_history') }}</button>
            <button type="button" wire:click="deleteAccount" wire:confirm="{{ __('app.delete_account_confirm') }}" class="rounded-xl bg-ink px-4 py-2 text-sm text-white">{{ __('app.delete_account') }}</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-xl border border-neutral-200 px-4 py-2 text-sm">{{ __('app.logout') }}</button>
            </form>
        </div>
    </div>

    @if ($requests->isEmpty())
        <x-card>
            <p>{{ __('app.history_empty') }}</p>
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-4">
            @foreach ($requests as $item)
                <x-card class="flex flex-col gap-4 sm:flex-row sm:items-center" wire:key="history-{{ $item->id }}">
                    @if ($item->thumbnail_url)
                        <img src="{{ $item->thumbnail_url }}" alt="" class="h-20 w-28 rounded-xl object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ '@'.$item->author_handle }}</p>
                        <p class="mt-1 line-clamp-2 text-sm text-neutral-600">{{ $item->text_excerpt }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $item->updated_at->locale(app()->getLocale())->isoFormat('D MMM YYYY') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('home', ['url' => $item->url]) }}" class="rounded-xl bg-ink px-4 py-2 text-sm text-white">{{ __('app.again') }}</a>
                        <button type="button" wire:click="delete({{ $item->id }})" class="rounded-xl border border-neutral-200 px-4 py-2 text-sm">{{ __('app.delete') }}</button>
                    </div>
                </x-card>
            @endforeach
        </div>
        <div class="mt-6">{{ $requests->links() }}</div>
    @endif
</div>
