<x-layouts.app :title="__('legal.contact_title')" :description="__('legal.contact_body')">
    <x-card class="mx-auto max-w-xl">
        <p class="text-xs font-medium tracking-wide text-muted uppercase">{{ __('app.draft') }}</p>
        <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('legal.contact_title') }}</h2>
        <p class="mt-4 leading-7">{{ __('legal.contact_body') }}</p>
        <p class="mt-2 text-sm"><a class="underline" href="mailto:{{ config('downloader.contact_email') }}">{{ config('downloader.contact_email') }}</a></p>
        @if (session('status'))
            <p class="mt-4 text-sm font-medium">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('legal.contact.store') }}" class="mt-6 space-y-4" x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf
            <x-honeypot />
            <label class="block text-sm">
                {{ __('app.email') }}
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            <label class="block text-sm">
                {{ __('app.message') }}
                <textarea name="message" required rows="5" class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">{{ old('message') }}</textarea>
            </label>
            @if ($errors->any())
                <p class="text-sm font-medium">{{ $errors->first() }}</p>
            @endif
            <button class="pg-press inline-flex items-center gap-2 rounded-2xl bg-ink px-5 py-3 text-white disabled:opacity-70" :disabled="sending">
                <span x-show="!sending">{{ __('app.send') }}</span>
                <span x-show="sending" x-cloak class="inline-flex items-center gap-2"><x-spinner /> {{ __('app.send') }}</span>
            </button>
        </form>
    </x-card>
</x-layouts.app>
