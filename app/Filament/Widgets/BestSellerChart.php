<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class BestSellerChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '🔥 Best Seller (Order Terbanyak)';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        [$start, $end] = $this->resolveRange();

        $rows = Order::query()
            ->select('book_id', DB::raw('COUNT(*) as orders'))
            ->whereIn('status', ['paid', 'watermarking', 'ready'])
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('book_id')
            ->orderByDesc('orders')
            ->with('book:id,title')
            ->take(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pembelian',
                    'data' => $rows->pluck('orders')->all(),
                    'backgroundColor' => '#10b981',
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
