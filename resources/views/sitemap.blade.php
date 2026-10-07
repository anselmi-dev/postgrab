{!! '<'.'?xml version="1.0" encoding="UTF-8"?>'."\n" !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach (\App\Support\Locales::codes() as $locale)
    <url>
        <loc>{{ route('home.locale', $locale) }}</loc>
@foreach (\App\Support\Locales::codes() as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ \App\Support\Locales::hreflang($alternate) }}" href="{{ route('home.locale', $alternate) }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ route('home.locale', \App\Support\Locales::default()) }}"/>
    </url>
@endforeach
@foreach (['legal.terms', 'legal.privacy', 'legal.dmca', 'legal.contact'] as $name)
    <url>
        <loc>{{ route($name) }}</loc>
    </url>
@endforeach
</urlset>
