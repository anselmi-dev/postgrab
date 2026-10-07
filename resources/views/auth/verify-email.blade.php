<x-layouts.app :title="__('app.verify_title')" robots="noindex, nofollow">
    <x-card class="mx-auto max-w-md">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.verify_title') }}</h2>
        <p class="mt-3 text-sm leading-6 text-neutral-600">{{ __('app.verify_body') }}</p>
        @if (session('status') == 'verification-link-sent')
            <p class="mt-4 text-sm">{{ __('app.resent') }}</p>
        @endif
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6" x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf
            <button class="pg-press inline-flex items-center gap-2 rounded-2xl bg-ink px-5 py-3 text-white disabled:opacity-70" :disabled="sending">
                <span x-show="!sending">{{ __('app.resend') }}</span>
                <span x-show="sending" x-cloak class="inline-flex items-center gap-2"><x-spinner /> {{ __('app.loading') }}</span>
            </button>
        </form>
    </x-card>
</x-layouts.app>
