<x-layouts.app>
    <x-card class="mx-auto max-w-md">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.forgot') }}</h2>
        @if (session('status'))
            <p class="mt-4 text-sm">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm">
                {{ __('app.email') }}
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            @if ($errors->any())
                <p class="text-sm font-medium">{{ $errors->first() }}</p>
            @endif
            <button class="w-full rounded-2xl bg-ink py-3 text-white">{{ __('app.send_link') }}</button>
        </form>
    </x-card>
</x-layouts.app>
