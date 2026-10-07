<x-layouts.app>
    <x-card class="mx-auto max-w-md">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.login') }}</h2>
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm">
                {{ __('app.email') }}
                <input name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            <label class="block text-sm">
                {{ __('app.password') }}
                <input name="password" type="password" required class="mt-1 w-full rounded-2xl bg-white px-4 py-3 outline-none ring-2 ring-transparent focus:ring-accent">
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="accent-ink"> {{ __('app.remember') }}
            </label>
            @if ($errors->any())
                <p class="text-sm font-medium">{{ $errors->first() }}</p>
            @endif
            @if (app(\App\Support\VerifyTurnstile::class)->configured() && \Illuminate\Support\Facades\RateLimiter::attempts('login-fails:'.request()->ip()) > 0)
                <div class="cf-turnstile" data-sitekey="{{ config('downloader.turnstile_site_key') }}"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif
            <button class="w-full rounded-2xl bg-ink py-3 text-white">{{ __('app.login') }}</button>
        </form>
        <p class="mt-4 text-sm"><a href="{{ route('password.request') }}" class="underline">{{ __('app.forgot') }}</a></p>
        <p class="mt-2 text-sm">{{ __('app.no_account') }} <a href="{{ route('register') }}" class="underline">{{ __('app.register') }}</a></p>
    </x-card>
</x-layouts.app>
