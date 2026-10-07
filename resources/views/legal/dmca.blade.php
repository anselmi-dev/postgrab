<x-layouts.app>
    <x-card class="mx-auto max-w-3xl">
        <p class="text-xs font-medium tracking-wide text-muted uppercase">{{ __('app.draft') }}</p>
        <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('legal.dmca_title') }}</h2>
        <p class="mt-4 leading-7">{{ __('legal.dmca_body') }}</p>
        <p class="mt-4 text-sm"><a class="underline" href="mailto:{{ config('downloader.dmca_email') }}">{{ config('downloader.dmca_email') }}</a></p>
    </x-card>
</x-layouts.app>
