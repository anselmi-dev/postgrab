@props([
    'title' => null,
    'description' => null,
    'robots' => 'index, follow',
    'canonical' => null,
])

@php
    $pageTitle = $title ? $title.' — '.config('app.name') : config('app.name');
    $pageDescription = $description ?: __('app.seo_description');
    $pageCanonical = $canonical ?: url()->current();
    $ogLocale = app()->getLocale() === 'en' ? 'en_US' : 'es_AR';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#0a0a0a">
    @if (request()->routeIs('home'))
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'WebApplication',
                'name' => config('app.name'),
                'url' => route('home'),
                'applicationCategory' => 'MultimediaApplication',
                'operatingSystem' => 'Web',
                'description' => __('app.seo_description'),
                'inLanguage' => ['es', 'en'],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-ink antialiased">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <header data-reveal class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <x-pill>{{ __('app.pill') }}</x-pill>
                <h1 class="mt-6 font-display text-4xl leading-none font-semibold tracking-tight sm:text-5xl">
                    <span class="block text-ink">{{ __('app.title_strong') }}</span>
                    <span class="mt-1 block text-muted">{{ __('app.title_muted') }}</span>
                </h1>
            </div>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                @foreach (['es', 'en'] as $locale)
                    <form method="POST" action="{{ route('locale', $locale) }}">
                        @csrf
                        <button type="submit" class="inline-flex size-10 items-center justify-center rounded-xl text-xs font-semibold sm:size-11 {{ app()->getLocale() === $locale ? 'bg-ink text-accent' : 'bg-neutral-100 text-neutral-500' }}" style="transform: rotate(45deg)">
                            <span style="transform: rotate(-45deg)">{{ strtoupper($locale) }}</span>
                        </button>
                    </form>
                @endforeach
                @auth
                    <a href="{{ route('history') }}" class="inline-flex size-10 items-center justify-center rounded-xl bg-ink text-accent sm:size-11" style="transform: rotate(45deg)" title="{{ __('app.history') }}">
                        <svg class="size-4" style="transform: rotate(-45deg)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="8"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex size-10 items-center justify-center rounded-xl border border-neutral-200 text-neutral-500 sm:size-11" style="transform: rotate(45deg)" title="{{ __('app.account') }}">
                        <svg class="size-4" style="transform: rotate(-45deg)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3"/><path d="M5 19c1.5-3 3.5-4.5 7-4.5S17.5 16 19 19"/></svg>
                    </a>
                @endauth
            </div>
        </header>

        <main data-reveal class="mt-10">
            {{ $slot }}
        </main>

        <footer data-reveal class="mt-14 flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
            <a href="{{ route('legal.terms') }}" class="hover:text-ink">{{ __('app.terms') }}</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-ink">{{ __('app.privacy') }}</a>
            <a href="{{ route('legal.dmca') }}" class="hover:text-ink">{{ __('app.dmca') }}</a>
            <a href="{{ route('legal.contact') }}" class="hover:text-ink">{{ __('app.contact') }}</a>
        </footer>
    </div>
    @livewireScripts
</body>
</html>
