<?php

use App\Filament\Widgets\UsageChart;
use App\Filament\Widgets\UsageOverview;
use App\Models\UsageEvent;
use App\Support\UsageSeries;

it('builds dashboard stats from today and yesterday', function () {
    UsageEvent::query()->create([
        'type' => 'lookup',
        'tweet_id' => '20',
        'driver' => 'fxtwitter',
        'success' => true,
        'units' => 1,
    ]);

    UsageEvent::query()->create([
        'type' => 'download',
        'tweet_id' => '20',
        'success' => true,
        'units' => 3,
    ]);

    $stats = UsageSeries::overview();

    expect($stats[0]['label'])->toBe('Consultas hoy')
        ->and($stats[0]['value'])->toBe('1')
        ->and($stats[0]['tone'])->toBe('up')
        ->and($stats[0]['bars'])->toHaveCount(7)
        ->and($stats[1]['value'])->toBe('3')
        ->and($stats[2]['value'])->toBe('100%');
});

it('charts consultas and descargas for each range', function () {
    UsageEvent::query()->create([
        'type' => 'lookup',
        'tweet_id' => '20',
        'success' => true,
        'units' => 1,
    ]);

    expect(UsageSeries::chart('semana')['buckets'])->toHaveCount(7)
        ->and(UsageSeries::chart('mes')['buckets'])->toHaveCount(30)
        ->and(UsageSeries::chart('ano')['buckets'])->toHaveCount(12)
        ->and(UsageSeries::chart('semana')['total'])->toBe('1');
});

it('renders the dashboard widgets and switches the chart range', function () {
    Livewire\Livewire::test(UsageOverview::class)
        ->assertSee('Consultas hoy')
        ->assertSee('Disco en uso');

    Livewire\Livewire::test(UsageChart::class)
        ->assertSee('Actividad')
        ->assertSee('Semana')
        ->call('setRange', 'ano')
        ->assertSet('range', 'ano')
        ->call('setRange', 'nope')
        ->assertSet('range', 'ano');
});
