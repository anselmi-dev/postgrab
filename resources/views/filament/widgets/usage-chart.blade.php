<x-filament-widgets::widget class="pg-chart-widget">
    <section class="pg-trend" data-count="{{ count($chart['buckets']) }}" wire:loading.class="is-loading" wire:target="setRange">
        <div class="pg-trend-head">
            <div>
                <p class="pg-kicker">
                    Actividad
                    <span class="pg-info" title="Consultas y descargas del período elegido.">i</span>
                </p>
                <p class="pg-metric-label">Consultas</p>
                <p class="pg-metric-value">{{ $chart['total'] }}</p>
            </div>
            <div class="pg-tools">
                <div class="pg-legend">
                    <span><i class="pg-key is-dotted"></i> Consultas</span>
                    <span><i class="pg-key"></i> Descargas</span>
                </div>
                <div class="pg-pills" role="group" aria-label="Período">
                    @foreach (['semana' => 'Semana', 'mes' => 'Mes', 'ano' => 'Año'] as $key => $label)
                        <button
                            type="button"
                            wire:click="setRange('{{ $key }}')"
                            @class(['is-active' => $range === $key])
                            @if ($range === $key) aria-pressed="true" @endif
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="pg-chart">
            <div class="pg-y" aria-hidden="true">
                @foreach ($chart['y'] as $label)
                    <span>{{ $label }}</span>
                @endforeach
            </div>
            <div class="pg-plot">
                <div class="pg-grid" aria-hidden="true"></div>
                <div class="pg-bars" data-count="{{ count($chart['buckets']) }}">
                    @foreach ($chart['buckets'] as $bucket)
                        <div class="pg-group" tabindex="0" aria-label="{{ $bucket['tip'] }}: {{ $bucket['lookups'] }} consultas, {{ $bucket['downloads'] }} descargas">
                            <div class="pg-pair">
                                <div class="pg-col" aria-hidden="true">
                                    @for ($square = 0; $square < 12; $square++)
                                        <span @class(['pg-sq', 'is-lookup' => $square < $bucket['lookup_squares']])></span>
                                    @endfor
                                </div>
                                <div class="pg-col" aria-hidden="true">
                                    @for ($square = 0; $square < 12; $square++)
                                        <span @class(['pg-sq', 'is-download' => $square < $bucket['download_squares']])></span>
                                    @endfor
                                </div>
                            </div>
                            <div class="pg-tip @if ($loop->first) is-start @endif @if ($loop->last) is-end @endif">
                                <p class="pg-tip-title">{{ $bucket['tip'] }}</p>
                                <p><i class="pg-swatch is-lookup"></i> Consultas <strong>{{ $bucket['lookups'] }}</strong></p>
                                <p><i class="pg-swatch is-download"></i> Descargas <strong>{{ $bucket['downloads'] }}</strong></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="pg-x" aria-hidden="true">
            <span class="pg-x-gap"></span>
            <div class="pg-x-labels">
                @foreach ($chart['buckets'] as $bucket)
                    <span>{{ $bucket['label'] }}</span>
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
