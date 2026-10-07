<x-layouts.app>
    <x-card class="mx-auto max-w-md">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.register') }}</h2>
        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            <x-honeypot />
            <label class="block text-sm">
                {{ __('app.name') }}
                <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            <label class="block text-sm">
                {{ __('app.email') }}
                <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
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
            @if (app(\App\Support\VerifyTurnstile::class)->configured())
                <div class="cf-turnstile" data-sitekey="{{ config('downloader.turnstile_site_key') }}"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif
            <button class="w-full rounded-2xl bg-ink py-3 text-white">{{ __('app.register') }}</button>
        </form>
        <p class="mt-4 text-sm">{{ __('app.has_account') }} <a href="{{ route('login') }}" class="underline">{{ __('app.login') }}</a></p>
    </x-card>
</x-layouts.app>
