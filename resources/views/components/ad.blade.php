@props(['placement'])
@php($html = \App\Support\Ads::html($placement))
@if ($html)
    <aside {{ $attributes->merge(['class' => 'rounded-card bg-surface p-4']) }}>
        <p class="mb-3 text-xs font-medium tracking-wide text-muted uppercase">{{ __('app.ad_label') }}</p>
        <div class="min-h-16 overflow-hidden">{!! $html !!}</div>
    </aside>
@endif
