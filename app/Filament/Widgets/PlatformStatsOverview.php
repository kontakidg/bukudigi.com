<?php

namespace App\Filament\Widgets;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookView;
use App\Models\Order;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        [$start, $end, $label] = $this->resolveRange();

        // Views dalam range
        $viewsInRange = BookView::whereBetween('viewed_at', [$start, $end])->count();

        // Orders dalam range (status = ready/paid)
        $ordersInRange = Order::whereIn('status', ['paid', 'watermarking', 'ready'])
            ->whereBetween('paid_at', [$start, $end])
            ->count();

        // Total books (active)
        $totalBooks = Book::where('status', 'active')->count();

        // Total verified authors
        $totalAuthors = Author::where('status', 'verified')->count();

        // Revenue dalam range
        $revenue = Order::whereIn('status', ['paid', 'watermarking', 'ready'])
            ->whereBetween('paid_at', [$start, $end])
            ->sum('gross_amount');

        return [
            Stat::make('Views ('.$label.')', number_format($viewsInRange))
                ->description('Total kunjungan halaman buku')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),
            Stat::make('Pembelian ('.$label.')', number_format($ordersInRange))
                ->description('Order sukses')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
            Stat::make('Revenue ('.$label.')', 'Rp '.number_format($revenue))
                ->description('Total gross')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Buku Live', number_format($totalBooks))
                ->description('Semua waktu')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),
            Stat::make('Penulis Verified', number_format($totalAuthors))
                ->description('Semua waktu')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }

    private function resolveRange(): array
    {
        $range = $this->filters['range'] ?? '30days';

        if ($range === 'custom') {
            $start = $this->filters['startDate'] ?? now()->subDays(30)->toDateString();
            $end = $this->filters['endDate'] ?? now()->toDateString();
            $label = $start.' s/d '.$end;
            return [
                \Carbon\Carbon::parse($start)->startOfDay(),
                \Carbon\Carbon::parse($end)->endOfDay(),
                $label,
            ];
        }

        return match ($range) {
            '6months' => [now()->subMonths(6), now(), '6 bln'],
            '1year' => [now()->subYear(), now(), '1 thn'],
            default => [now()->subDays(30), now(), '1 bln'],
        };
    }
}
