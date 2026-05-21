<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\PlatformStatsOverview::class,
            \App\Filament\Widgets\TrendsChart::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('range')
                        ->label('Rentang waktu')
                        ->options([
                            '30days' => '1 Bulan',
                            '6months' => '6 Bulan',
                            '1year' => '1 Tahun',
                            'custom' => 'Custom',
                        ])
                        ->default('30days')
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state !== 'custom') {
                                $set('startDate', null);
                                $set('endDate', null);
                            }
                        }),
                    Forms\Components\DatePicker::make('startDate')
                        ->label('Dari tanggal')
                        ->visible(fn ($get) => $get('range') === 'custom'),
                    Forms\Components\DatePicker::make('endDate')
                        ->label('Sampai tanggal')
                        ->visible(fn ($get) => $get('range') === 'custom'),
                ])->columns(3),
        ]);
    }
}
