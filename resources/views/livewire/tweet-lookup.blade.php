<div @if($pendingFileId) wire:poll.2s="refreshDownload" @endif>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <form wire:submit="lookup" class="flex flex-col gap-3 sm:flex-row">
                <input wire:model="url" type="url" required placeholder="{{ __('app.url_placeholder') }}" class="w-full rounded-2xl bg-white px-5 py-4 text-base text-ink outline-none ring-2 ring-transparent placeholder:text-muted focus:ring-accent">
                <button type="submit" class="rounded-2xl bg-ink px-6 py-4 font-medium text-white" wire:loading.attr="disabled" wire:target="lookup">
                    <span wire:loading.remove wire:target="lookup">{{ __('app.search') }}</span>
                    <span wire:loading wire:target="lookup">…</span>
                </button>
            </form>

            @if ($challenge)
                <div class="mt-4" wire:ignore>
                    <div class="cf-turnstile" data-sitekey="{{ config('downloader.turnstile_site_key') }}" data-callback="postgrabTurnstile"></div>
                </div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                <script>
                    function postgrabTurnstile(token) {
                        const component = Livewire.find(@js($this->getId()));
                        component?.set('turnstileToken', token);
                    }
                </script>
            @endif

            @if ($error)
                <p class="mt-5 text-sm font-medium text-ink">{{ $error }}</p>
            @endif

            @if ($tweet)
                <article class="mt-8">
                    <div class="flex items-center gap-3">
                        @if ($tweet['avatar_url'])
                            <img src="{{ $tweet['avatar_url'] }}" alt="" class="size-11 rounded-full object-cover">
                        @endif
                        <div>
                            <p class="font-semibold">{{ $tweet['author_name'] }}</p>
                            <p class="text-sm text-muted">{{ '@'.$tweet['author_handle'] }}
                                @if ($tweet['created_at'])
                                    · {{ \Carbon\CarbonImmutable::parse($tweet['created_at'])->locale(app()->getLocale())->isoFormat('D MMM YYYY') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <p class="mt-4 whitespace-pre-line leading-7 text-ink">{{ $tweet['text'] }}</p>
                    <button type="button" class="mt-3 text-sm font-medium underline decoration-neutral-300 underline-offset-4" x-data="{ copied: false }" x-on:click="navigator.clipboard.writeText(@js($tweet['text'])); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-show="!copied">{{ __('app.copy') }}</span>
                        <span x-show="copied" x-cloak>{{ __('app.copied') }}</span>
                    </button>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($tweet['media'] as $media)
                            <div class="rounded-2xl bg-white p-4" wire:key="media-{{ $media['id'] }}">
                                <div class="mb-3 flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ $media['index'] }} {{ __('app.of') }} {{ count($tweet['media']) }}</span>
                                    <span class="text-muted">{{ __('app.'.$media['type']) }}</span>
                                </div>
                                @if ($media['thumbnail_url'])
                                    <img src="{{ $media['thumbnail_url'] }}" alt="" class="mb-3 aspect-video w-full rounded-xl object-cover">
                                @endif
                                @if (count($media['variants']) > 1)
                                    <label class="mb-3 block text-sm">
                                        <span class="text-muted">{{ __('app.quality') }}</span>
                                        <select wire:model="qualities.{{ $media['id'] }}" class="mt-1 w-full rounded-xl bg-surface px-3 py-2">
                                            @foreach ($media['variants'] as $variant)
                                                <option value="{{ $variant['key'] }}">
                                                    {{ $variant['label'] }}
                                                    @if ($variant['size_bytes'])
                                                        · ~{{ $bytes::human($variant['size_bytes']) }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                @else
                                    <p class="mb-3 text-sm text-muted">{{ $bytes::human($media['variants'][0]['size_bytes'] ?? null) ?? __('app.size_unknown') }}</p>
                                @endif
                                <div class="flex items-center justify-between gap-3">
                                    @if (count($tweet['media']) > 1)
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" value="{{ $media['id'] }}" wire:model="selected" class="size-4 accent-ink">
                                        </label>
                                    @endif
                                    <button type="button" wire:click="download('{{ $media['id'] }}')" class="ml-auto rounded-xl bg-ink px-4 py-2 text-sm text-white">{{ __('app.download') }}</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @else
                <p class="mt-8 text-sm leading-6 text-neutral-600">{{ __('app.preview_empty') }}</p>
            @endif
        </x-card>

        <x-card>
            <x-ad placement="middle" class="mb-6 !p-0" />
            <h2 class="font-display text-2xl font-semibold">{{ __('app.actions') }}</h2>
            <p class="mt-2 text-sm leading-6 text-neutral-600">{{ __('app.actions_hint') }}</p>

            @if ($lockedUntil)
                <p class="mt-4 text-sm font-medium">{{ __('app.locked', ['time' => $lockedUntil->locale(app()->getLocale())->isoFormat('HH:mm')]) }}</p>
            @else
                <p class="mt-4 inline-flex rounded-full bg-accent px-3 py-1 text-sm font-medium text-ink">{{ __('app.remaining', ['count' => $remaining]) }}</p>
            @endif

            @if ($tweet && count($tweet['media']) > 1)
                <div class="mt-6 flex flex-col gap-3">
                    <button type="button" wire:click="downloadSelected" class="rounded-2xl bg-ink px-4 py-3 text-sm text-white">{{ __('app.download_selected') }}</button>
                    <button type="button" wire:click="downloadAll" class="rounded-2xl border border-ink px-4 py-3 text-sm">{{ __('app.download_all') }}</button>
                    <p class="text-xs leading-5 text-neutral-500 lg:hidden">{{ __('app.zip_mobile') }}</p>
                </div>
            @endif

            @if ($progress)
                <p class="mt-4 text-sm">{{ $progress }}</p>
            @endif

            @if ($downloadUrl)
                <a href="{{ $downloadUrl }}" class="mt-4 inline-flex rounded-2xl bg-accent px-4 py-3 text-sm font-medium text-ink">{{ __('app.save_file') }}</a>
                <p class="mt-2 text-xs text-neutral-500">{{ __('app.ready') }}</p>
            @endif
        </x-card>
    </div>
</div>
