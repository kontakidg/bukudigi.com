<?php

namespace App\Filament\Widgets;

use App\Models\BookView;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class TopViewedBooksChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '👁️ Buku Paling Banyak Dilihat';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        [$start, $end] = $this->resolveRange();

        $rows = BookView::query()
            ->select('book_id', DB::raw('COUNT(*) as views'))
            ->whereBetween('viewed_at', [$start, $end])
            ->groupBy('book_id')
            ->orderByDesc('views')
            ->with('book:id,title')
            ->take(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Views',
                    'data' => $rows->pluck('views')->all(),
                    'backgroundColor' => '#4f46e5',
                ],
            ],
            'labels' => $rows->map(fn ($r) => \Illuminate\Support\Str::limit($r->book?->title ?? '(deleted)', 30))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true]],
        ];
    }

    private function resolveRange(): array
    {
        $range = $this->filters['range'] ?? '30days';
        if ($range === 'custom') {
            $start = $this->filters['startDate'] ?? now()->subDays(30)->toDateString();
            $end = $this->filters['endDate'] ?? now()->toDateString();
            return [
                \Carbon\Carbon::parse($start)->startOfDay(),
                \Carbon\Carbon::parse($end)->endOfDay(),
            ];
        }
        return match ($range) {
            '6months' => [now()->subMonths(6), now()],
            '1year' => [now()->subYear(), now()],
            default => [now()->subDays(30), now()],
        };
    }
}
