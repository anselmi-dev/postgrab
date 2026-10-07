<x-filament-widgets::widget class="pg-overview">
    <div class="pg-stats">
        @foreach ($stats as $stat)
            <article class="pg-stat" wire:key="stat-{{ $stat['label'] }}">
                <p class="pg-stat-label">{{ $stat['label'] }}</p>
                <div class="pg-stat-main">
                    <p class="pg-stat-value">{{ $stat['value'] }}</p>
                    <div class="pg-spark" aria-hidden="true">
                        @foreach ($stat['bars'] as $height)
                            <span @class(['is-last' => $loop->last]) style="height: {{ $height }}%"></span>
                        @endforeach
                    </div>
                </div>
                <p @class(['pg-stat-delta', 'is-'.$stat['tone']])>{{ $stat['delta'] }}</p>
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
