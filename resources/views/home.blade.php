<x-layouts.app :title="__('app.seo_title')" :canonical="route('home')">
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

    <x-ad placement="footer" class="mt-10" />
</x-layouts.app>
