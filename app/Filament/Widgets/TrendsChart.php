<?php

namespace App\Filament\Widgets;

use App\Models\BookView;
use App\Models\Order;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TrendsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '📈 Trend Platform';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '380px';

    protected function getData(): array
    {
        [$start, $end] = $this->resolveRange();

        $viewsByDay = BookView::query()
            ->whereBetween('viewed_at', [$start, $end])
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $ordersByDay = Order::query()
            ->whereIn('status', ['paid', 'watermarking', 'ready'])
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DATE(paid_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt', 'day');

        $revenueByDay = Order::query()
            ->whereIn('status', ['paid', 'watermarking', 'ready'])
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DATE(paid_at) as day, SUM(gross_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        // Build daily series across range
        $period = CarbonPeriod::create($start->copy()->startOfDay(), '1 day', $end->copy()->startOfDay());
        $labels = [];
        $views = [];
        $orders = [];
        $revenue = [];

        foreach ($period as $date) {
            $key = $date->toDateString();
            $labels[] = $date->isoFormat('DD MMM');
            $views[] = (int) ($viewsByDay->get($key, 0));
            $orders[] = (int) ($ordersByDay->get($key, 0));
            $revenue[] = (int) ($revenueByDay->get($key, 0));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Views',
                    'data' => $views,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Pembelian',
                    'data' => $orders,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.3,
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Revenue (Rp)',
                    'data' => $revenue,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.3,
                    'borderWidth' => 2,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Views & Pembelian',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue (Rp)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
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
