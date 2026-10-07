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
    $currentLocale = app()->getLocale();
    $ogLocale = \App\Support\Locales::og($currentLocale);
    $isLocalizedHome = request()->routeIs('home.locale');
    $schema = null;

    if ($isLocalizedHome) {
        $steps = __('app.seo_steps');
        $faq = __('app.seo_faq');
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebApplication',
                    'name' => config('app.name'),
                    'url' => \App\Support\Locales::url(),
                    'applicationCategory' => 'MultimediaApplication',
                    'operatingSystem' => 'Web',
                    'description' => __('app.seo_description'),
                    'inLanguage' => array_map(fn (string $code) => \App\Support\Locales::hreflang($code), \App\Support\Locales::codes()),
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'USD',
                    ],
                ],
                [
                    '@type' => 'HowTo',
                    'name' => __('app.seo_how_title'),
                    'description' => __('app.seo_description'),
                    'inLanguage' => \App\Support\Locales::hreflang($currentLocale),
                    'step' => collect(is_array($steps) ? $steps : [])->values()->map(fn (array $step, int $index) => [
                        '@type' => 'HowToStep',
                        'position' => $index + 1,
                        'name' => $step['title'],
                        'text' => $step['body'],
                    ])->all(),
                ],
                [
                    '@type' => 'FAQPage',
                    'inLanguage' => \App\Support\Locales::hreflang($currentLocale),
                    'mainEntity' => collect(is_array($faq) ? $faq : [])->map(fn (array $item) => [
                        '@type' => 'Question',
                        'name' => $item['q'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $item['a'],
                        ],
                    ])->all(),
                ],
            ],
        ];
    }
@endphp

<!DOCTYPE html>
<html lang="{{ \App\Support\Locales::hreflang($currentLocale) }}">
<head>
    <meta charset="utf-8">
    <script>
        if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion')
            window.setTimeout(() => {
                if (! window.__revealStarted) {
                    document.documentElement.classList.remove('motion')
                }
            }, 2000)
        }
    </script>
    <style>
        html.motion [data-reveal] { opacity: 0; transform: translateY(18px); }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $pageCanonical }}">
    @if ($isLocalizedHome)
        @foreach (\App\Support\Locales::codes() as $code)
            <link rel="alternate" hreflang="{{ \App\Support\Locales::hreflang($code) }}" href="{{ route('home.locale', $code) }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ route('home.locale', \App\Support\Locales::default()) }}">
    @endif
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    @if ($isLocalizedHome)
        @foreach (\App\Support\Locales::codes() as $code)
            @if ($code !== $currentLocale)
                <meta property="og:locale:alternate" content="{{ \App\Support\Locales::og($code) }}">
            @endif
        @endforeach
    @endif
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#0a0a0a">
    @if ($schema)
        <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-ink antialiased">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <header data-reveal>
            <div class="flex items-start justify-between gap-4">
                <a href="{{ \App\Support\Locales::url() }}" class="no-underline">
                    <x-pill>{{ __('app.pill') }}</x-pill>
                </a>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <details class="relative">
                    <summary class="inline-flex size-10 list-none items-center justify-center rounded-xl bg-ink text-xs font-semibold text-accent marker:hidden sm:size-11 [&::-webkit-details-marker]:hidden" style="transform: rotate(45deg)" aria-label="{{ __('app.language') }}">
                        <span style="transform: rotate(-45deg)">{{ strtoupper($currentLocale) }}</span>
                    </summary>
                    <div class="absolute right-0 z-20 mt-3 w-44 rounded-2xl border border-neutral-200 bg-white p-2 shadow-lg">
                        @foreach (\App\Support\Locales::codes() as $code)
                            <a href="{{ route('home.locale', $code) }}" hreflang="{{ \App\Support\Locales::hreflang($code) }}" lang="{{ \App\Support\Locales::hreflang($code) }}" class="block rounded-xl px-3 py-2 text-sm no-underline {{ $currentLocale === $code ? 'bg-accent font-medium text-ink' : 'text-neutral-600' }}" @if ($currentLocale === $code) aria-current="true" @endif>{{ \App\Support\Locales::label($code) }}</a>
                        @endforeach
                    </div>
                </details>
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
            </div>
            <a href="{{ \App\Support\Locales::url() }}" class="mt-6 block no-underline">
                <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight sm:text-5xl sm:leading-none">
                    <span class="block text-balance text-ink">{{ __('app.title_strong') }}</span>
                    <span class="mt-1 block text-balance text-muted">{{ __('app.title_muted') }}</span>
                </h1>
            </a>
        </header>

        <main data-reveal class="mt-10">
            {{ $slot }}
        </main>

        <footer data-reveal class="mt-14">
            <nav aria-label="{{ __('app.language') }}" class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                @foreach (\App\Support\Locales::codes() as $code)
                    <a href="{{ route('home.locale', $code) }}" hreflang="{{ \App\Support\Locales::hreflang($code) }}" lang="{{ \App\Support\Locales::hreflang($code) }}" class="{{ $currentLocale === $code ? 'font-medium text-ink' : 'text-muted hover:text-ink' }}">{{ \App\Support\Locales::label($code) }}</a>
                @endforeach
            </nav>
            <nav class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted">
                <a href="{{ route('legal.terms') }}" class="hover:text-ink">{{ __('app.terms') }}</a>
                <a href="{{ route('legal.privacy') }}" class="hover:text-ink">{{ __('app.privacy') }}</a>
                <a href="{{ route('legal.dmca') }}" class="hover:text-ink">{{ __('app.dmca') }}</a>
                <a href="{{ route('legal.contact') }}" class="hover:text-ink">{{ __('app.contact') }}</a>
            </nav>
        </footer>
    </div>
    @livewireScripts
</body>
</html>
