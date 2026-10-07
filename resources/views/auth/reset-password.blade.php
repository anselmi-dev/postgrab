<x-layouts.app :title="__('app.reset')" robots="noindex, nofollow">
    <x-card class="mx-auto max-w-md">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.reset') }}</h2>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4" x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <label class="block text-sm">
                {{ __('app.email') }}
                <input name="email" type="email" value="{{ old('email', $request->email) }}" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            <label class="block text-sm">
                {{ __('app.password') }}
                <input name="password" type="password" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            <label class="block text-sm">
                {{ __('app.password_confirmation') }}
                <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            @if ($errors->any())
                <p class="text-sm font-medium">{{ $errors->first() }}</p>
            @endif
            <button class="pg-press inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-ink py-3 text-white disabled:opacity-70" :disabled="sending">
                <span x-show="!sending">{{ __('app.reset') }}</span>
                <span x-show="sending" x-cloak class="inline-flex items-center gap-2"><x-spinner /> {{ __('app.loading') }}</span>
            </button>
        </form>
    </x-card>
</x-layouts.app>
