@props(['title'])
<div class="flex items-start gap-4">
    <div data-motion="pop" class="flex size-12 shrink-0 items-center justify-center rounded-full bg-accent text-ink">
        {{ $icon }}
    </div>
    <div>
        <h3 class="font-semibold text-ink">{{ $title }}</h3>
        <p class="mt-1 text-sm leading-6 text-neutral-600">{{ $slot }}</p>
    </div>
</div>
