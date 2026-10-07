<x-layouts.app :title="__('app.seo_title')" :canonical="\App\Support\Locales::url()">
    <x-ad placement="top" class="mb-6" />

    <livewire:tweet-lookup />

    <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-3">
        <x-feature :title="__('app.feature_quality_title')">
            <x-slot:icon>
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 2.2 4.8L19 10l-4.8 2.2L12 17l-2.2-4.8L5 10l4.8-2.2z"/></svg>
            </x-slot:icon>
            {{ __('app.feature_quality_body') }}
        </x-feature>
        <x-feature :title="__('app.feature_zip_title')">
            <x-slot:icon>
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v12H4z"/><path d="M8 7V5h8v2"/></svg>
            </x-slot:icon>
            {{ __('app.feature_zip_body') }}
        </x-feature>
        <x-feature :title="__('app.feature_history_title')">
            <x-slot:icon>
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3"/><path d="M5 19c1.5-3 3.5-4.5 7-4.5S17.5 16 19 19"/></svg>
            </x-slot:icon>
            {{ __('app.feature_history_body') }}
        </x-feature>
    </div>

    <section class="mt-16 max-w-3xl">
        <h2 class="font-display text-3xl font-semibold tracking-tight">{{ __('app.seo_intro_title') }}</h2>
        <p class="mt-4 text-sm leading-7 text-neutral-600">{{ __('app.seo_intro_body') }}</p>
        <p class="mt-3 text-sm leading-7 text-neutral-600">{{ __('app.seo_intro_body_2') }}</p>
    </section>

    <section class="mt-14">
        <h2 class="font-display text-3xl font-semibold tracking-tight">{{ __('app.seo_how_title') }}</h2>
        <ol class="mt-8 grid grid-cols-1 gap-8 sm:grid-cols-3">
            @foreach (__('app.seo_steps') as $index => $step)
                <li>
                    <div class="flex size-12 items-center justify-center rounded-full bg-accent font-display text-lg font-semibold text-ink">{{ $index + 1 }}</div>
                    <h3 class="mt-4 font-semibold text-ink">{{ $step['title'] }}</h3>
                    <p class="mt-1 text-sm leading-6 text-neutral-600">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
        <p class="mt-8 text-sm text-neutral-600">{{ __('app.seo_examples_label') }}</p>
        <ul class="mt-3 space-y-2">
            @foreach (__('app.seo_examples') as $example)
                <li class="overflow-x-auto rounded-2xl bg-surface px-4 py-3 font-mono text-sm text-ink">{{ $example }}</li>
            @endforeach
        </ul>
    </section>

    <section class="mt-14 max-w-3xl">
        <h2 class="font-display text-3xl font-semibold tracking-tight">{{ __('app.seo_formats_title') }}</h2>
        <ul class="mt-6 space-y-3">
            @foreach (__('app.seo_formats') as $item)
                <li class="flex gap-3 text-sm leading-6 text-neutral-600">
                    <span class="mt-2 size-2 shrink-0 rounded-full bg-accent"></span>
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mt-14 max-w-3xl" id="faq">
        <h2 class="font-display text-3xl font-semibold tracking-tight">{{ __('app.seo_faq_title') }}</h2>
        <div class="mt-6 border-t border-neutral-200">
            @foreach (__('app.seo_faq') as $item)
                <details class="group border-b border-neutral-200 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-ink marker:hidden [&::-webkit-details-marker]:hidden">
                        {{ $item['q'] }}
                        <span class="text-xl leading-none text-muted transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-6 text-neutral-600">{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
        <p class="mt-6 text-sm leading-6 text-neutral-600">
            {{ __('app.seo_notice') }}
            <a href="{{ route('legal.dmca') }}" class="underline hover:text-ink">{{ __('app.seo_notice_link') }}</a>
        </p>
    </section>

    <x-ad placement="footer" class="mt-10" />
</x-layouts.app>
