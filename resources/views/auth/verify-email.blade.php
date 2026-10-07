<x-layouts.app>
    <x-card class="mx-auto max-w-md">
        <h2 class="font-display text-3xl font-semibold">{{ __('app.verify_title') }}</h2>
        <p class="mt-3 text-sm leading-6 text-neutral-600">{{ __('app.verify_body') }}</p>
        @if (session('status') == 'verification-link-sent')
            <p class="mt-4 text-sm">{{ __('app.resent') }}</p>
        @endif
        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button class="rounded-2xl bg-ink px-5 py-3 text-white">{{ __('app.resend') }}</button>
        </form>
    </x-card>
</x-layouts.app>
