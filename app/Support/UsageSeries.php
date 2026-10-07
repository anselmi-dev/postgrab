<?php

namespace App\Support;

use App\Models\DownloadedFile;
use App\Models\UsageEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class UsageSeries
{
    /**
     * @return list<array{label: string, value: string, delta: string, tone: string, bars: list<int>}>
     */
    public static function overview(): array
    {
        $days = self::days(7);
        $usage = self::usageByDay($days[0]);
        $today = $days[6]->toDateString();
        $yesterday = $days[5]->toDateString();

        $lookupToday = self::usage($usage, $today, 'lookup');
        $lookupYesterday = self::usage($usage, $yesterday, 'lookup');
        $downloadToday = self::usage($usage, $today, 'download');
        $downloadYesterday = self::usage($usage, $yesterday, 'download');

        $rates = self::driverRates($days[0]);
        $recent = self::driverWindow(now()->subDay(), now(), inclusive: true);
        $previous = self::driverWindow(now()->subDays(2), now()->subDay());

        $disk = (int) DownloadedFile::query()
            ->whereNotNull('ready_at')
            ->where('expires_at', '>', now())
            ->sum('size_bytes');
        $ready = DownloadedFile::query()
            ->whereNotNull('ready_at')
            ->where('expires_at', '>', now())
            ->count();
        $diskBars = self::diskBars($days);

        return [
            [
                'label' => 'Consultas hoy',
                'value' => self::number($lookupToday),
                'delta' => self::delta($lookupToday, $lookupYesterday)['text'],
                'tone' => self::delta($lookupToday, $lookupYesterday)['tone'],
                'bars' => self::bars(array_map(fn (Carbon $day) => self::usage($usage, $day->toDateString(), 'lookup'), $days)),
            ],
            [
                'label' => 'Descargas hoy',
                'value' => self::number($downloadToday),
                'delta' => self::delta($downloadToday, $downloadYesterday)['text'],
                'tone' => self::delta($downloadToday, $downloadYesterday)['tone'],
                'bars' => self::bars(array_map(fn (Carbon $day) => self::usage($usage, $day->toDateString(), 'download'), $days)),
            ],
            [
                'label' => 'Éxito FxTwitter',
                'value' => $recent['total'] === 0 ? '—' : $recent['percent'].'%',
                'delta' => self::points($recent, $previous),
                'tone' => self::pointsTone($recent, $previous),
                'bars' => self::bars(array_map(fn (Carbon $day) => $rates[$day->toDateString()] ?? 0, $days)),
            ],
            [
                'label' => 'Disco en uso',
                'value' => Bytes::human($disk) ?? '0 B',
                'delta' => $ready === 1 ? '1 archivo listo' : $ready.' archivos listos',
                'tone' => 'flat',
                'bars' => $diskBars,
            ],
        ];
    }

    /**
     * @return array{total: string, y: list<string>, buckets: list<array{label: string, tip: string, lookups: int, downloads: int, lookup_squares: int, download_squares: int}>}
     */
    public static function chart(string $range): array
    {
        $steps = 12;
        $buckets = match ($range) {
            'mes' => self::dayBuckets(30),
            'ano' => self::monthBuckets(12),
            default => self::dayBuckets(7),
        };

        $max = 0;

        foreach ($buckets as $bucket) {
            $max = max($max, $bucket['lookups'], $bucket['downloads']);
        }

        $scale = max($max, 1);

        foreach ($buckets as $index => $bucket) {
            $buckets[$index]['lookup_squares'] = (int) round($bucket['lookups'] / $scale * $steps);
            $buckets[$index]['download_squares'] = (int) round($bucket['downloads'] / $scale * $steps);
        }

        $total = array_sum(array_column($buckets, 'lookups'));

        return [
            'total' => self::number($total),
            'y' => [
                self::compact((int) round($max)),
                self::compact((int) round($max * 0.75)),
                self::compact((int) round($max * 0.5)),
                self::compact((int) round($max * 0.25)),
                '0',
            ],
            'buckets' => $buckets,
        ];
    }

    /**
     * @return list<Carbon>
     */
    private static function days(int $count): array
    {
        return array_map(
            fn (int $ago) => Carbon::today()->subDays($count - 1 - $ago),
            range(0, $count - 1),
        );
    }

    /**
     * @param  list<Carbon>  $days
     * @return Collection<string, array{lookup: int, download: int}>
     */
    private static function usageByDay(Carbon $from): Collection
    {
        return UsageEvent::query()
            ->where('created_at', '>=', $from->copy()->startOfDay())
            ->selectRaw('date(created_at) as bucket, type, count(*) as events, coalesce(sum(units), 0) as units')
            ->groupByRaw('date(created_at)')
            ->groupBy('type')
            ->get()
            ->groupBy('bucket')
            ->map(function (Collection $rows): array {
                $lookup = $rows->firstWhere('type', 'lookup');
                $download = $rows->firstWhere('type', 'download');

                return [
                    'lookup' => (int) ($lookup->events ?? 0),
                    'download' => (int) ($download->units ?? 0),
                ];
            });
    }

    /**
     * @param  Collection<string, array{lookup: int, download: int}>  $usage
     */
    private static function usage(Collection $usage, string $day, string $type): int
    {
        return (int) ($usage[$day][$type] ?? 0);
    }

    /**
     * @param  list<Carbon>  $days
     * @return array<string, int>
     */
    private static function driverRates(Carbon $from): array
    {
        return UsageEvent::query()
            ->where('type', 'lookup')
            ->where('driver', 'fxtwitter')
            ->where('created_at', '>=', $from->copy()->startOfDay())
            ->selectRaw('date(created_at) as bucket, count(*) as events, sum(case when success = 1 then 1 else 0 end) as ok')
            ->groupByRaw('date(created_at)')
            ->get()
            ->mapWithKeys(function ($row): array {
                $events = (int) $row->events;

                return [$row->bucket => $events === 0 ? 0 : (int) round(((int) $row->ok / $events) * 100)];
            })
            ->all();
    }

    /**
     * @return array{total: int, percent: int}
     */
    private static function driverWindow(Carbon $from, Carbon $until, bool $inclusive = false): array
    {
        $row = UsageEvent::query()
            ->where('type', 'lookup')
            ->where('driver', 'fxtwitter')
            ->where('created_at', '>=', $from)
            ->where('created_at', $inclusive ? '<=' : '<', $until)
            ->selectRaw('count(*) as events, sum(case when success = 1 then 1 else 0 end) as ok')
            ->first();

        $total = (int) ($row->events ?? 0);

        return [
            'total' => $total,
            'percent' => $total === 0 ? 0 : (int) round(((int) $row->ok / $total) * 100),
        ];
    }

    /**
     * @param  list<Carbon>  $days
     * @return list<int>
     */
    private static function diskBars(array $days): array
    {
        $sizes = DownloadedFile::query()
            ->where('created_at', '>=', $days[0]->copy()->startOfDay())
            ->selectRaw('date(created_at) as bucket, coalesce(sum(size_bytes), 0) as bytes')
            ->groupByRaw('date(created_at)')
            ->pluck('bytes', 'bucket');

        return self::bars(array_map(
            fn (Carbon $day) => (int) ($sizes[$day->toDateString()] ?? 0),
            $days,
        ));
    }

    /**
     * @return list<array{label: string, tip: string, lookups: int, downloads: int}>
     */
    private static function dayBuckets(int $count): array
    {
        $days = self::days($count);
        $usage = self::usageByDay($days[0]);
        $week = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];

        return array_map(function (Carbon $day) use ($usage, $week, $count): array {
            $key = $day->toDateString();

            return [
                'label' => $count > 7 ? $day->format('j') : $week[$day->dayOfWeek],
                'tip' => $day->format('j').' '.self::month($day->month).' '.$day->year,
                'lookups' => self::usage($usage, $key, 'lookup'),
                'downloads' => self::usage($usage, $key, 'download'),
            ];
        }, $days);
    }

    /**
     * @return list<array{label: string, tip: string, lookups: int, downloads: int}>
     */
    private static function monthBuckets(int $count): array
    {
        $start = Carbon::today()->startOfMonth()->subMonths($count - 1);
        $usage = UsageEvent::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('substr(date(created_at), 1, 7) as bucket, type, count(*) as events, coalesce(sum(units), 0) as units')
            ->groupByRaw('substr(date(created_at), 1, 7)')
            ->groupBy('type')
            ->get()
            ->groupBy('bucket');

        $buckets = [];

        foreach (range(0, $count - 1) as $offset) {
            $month = $start->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            $rows = $usage[$key] ?? collect();
            $lookup = $rows->firstWhere('type', 'lookup');
            $download = $rows->firstWhere('type', 'download');

            $buckets[] = [
                'label' => self::month($month->month),
                'tip' => ucfirst(self::month($month->month)).' '.$month->year,
                'lookups' => (int) ($lookup->events ?? 0),
                'downloads' => (int) ($download->units ?? 0),
            ];
        }

        return $buckets;
    }

    /**
     * @param  list<int>  $values
     * @return list<int>
     */
    private static function bars(array $values): array
    {
        $max = max($values ?: [0]);

        if ($max < 1) {
            return array_fill(0, count($values), 18);
        }

        return array_map(
            fn (int $value) => max(18, (int) round($value / $max * 100)),
            $values,
        );
    }

    /**
     * @return array{text: string, tone: string}
     */
    private static function delta(int $current, int $previous): array
    {
        $diff = $current - $previous;

        if ($diff === 0) {
            return ['text' => 'sin cambios vs ayer', 'tone' => 'flat'];
        }

        return [
            'text' => ($diff > 0 ? '+' : '−').self::number(abs($diff)).' vs ayer',
            'tone' => $diff > 0 ? 'up' : 'down',
        ];
    }

    /**
     * @param  array{total: int, percent: int}  $recent
     * @param  array{total: int, percent: int}  $previous
     */
    private static function points(array $recent, array $previous): string
    {
        if ($recent['total'] === 0 && $previous['total'] === 0) {
            return 'sin datos en 24 h';
        }

        $diff = $recent['percent'] - $previous['percent'];

        if ($diff === 0) {
            return 'sin cambios vs ayer';
        }

        return ($diff > 0 ? '+' : '−').abs($diff).' pts vs ayer';
    }

    /**
     * @param  array{total: int, percent: int}  $recent
     * @param  array{total: int, percent: int}  $previous
     */
    private static function pointsTone(array $recent, array $previous): string
    {
        if ($recent['total'] === 0 && $previous['total'] === 0) {
            return 'flat';
        }

        $diff = $recent['percent'] - $previous['percent'];

        return match (true) {
            $diff > 0 => 'up',
            $diff < 0 => 'down',
            default => 'flat',
        };
    }

    private static function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private static function compact(int $value): string
    {
        if ($value >= 1000000) {
            return rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',').'M';
        }

        if ($value >= 1000) {
            return rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',').'k';
        }

        return (string) $value;
    }

    private static function month(int $month): string
    {
        return ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][$month - 1];
    }
}
