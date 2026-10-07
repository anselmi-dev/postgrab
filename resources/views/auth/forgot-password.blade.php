<x-layouts.app :title="__('app.forgot')" robots="noindex, nofollow">
    <x-card class="mx-auto max-w-md">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.forgot') }}</h2>
        @if (session('status'))
            <p class="mt-4 text-sm">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4" x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf
            <label class="block text-sm">
                {{ __('app.email') }}
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            @if ($errors->any())
                <p class="text-sm font-medium">{{ $errors->first() }}</p>
            @endif
            <button class="pg-press inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-ink py-3 text-white disabled:opacity-70" :disabled="sending">
                <span x-show="!sending">{{ __('app.send_link') }}</span>
                <span x-show="sending" x-cloak class="inline-flex items-center gap-2"><x-spinner /> {{ __('app.loading') }}</span>
            </button>
        </form>
    </x-card>
</x-layouts.app>
