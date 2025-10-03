<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Invoice;
use App\Models\Bill;
use Illuminate\Support\Carbon;

class RevenueExpenseChart extends ChartWidget
{
    protected static ?string $heading = 'Revenus vs Dépenses (12 derniers mois)';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';

    public ?string $filter = 'year';

    protected int | string | array $columnSpan = 'full';

    protected function getFilters(): ?array
    {
        return [
            'year' => '12 mois',
            'quarter' => '3 mois',
            'month' => 'Ce mois',
        ];
    }

    protected function getData(): array
    {
        $months = match($this->filter) {
            'year' => 12,
            'quarter' => 3,
            'month' => 1,
            default => 12,
        };

        $startDate = now()->subMonths($months - 1)->startOfMonth();

        $revenues = [];
        $expenses = [];
        $labels = [];

        for ($i = 0; $i < $months; $i++) {
            $date = $startDate->copy()->addMonths($i);

            $revenues[] = Invoice::where('status', 'paid')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('total');

            $expenses[] = Bill::where('status', 'paid')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('total');

            $labels[] = $date->locale('fr')->isoFormat('MMM YYYY');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenus ('. currency()->symbol .')',
                    'data' => $revenues,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
                [
                    'label' => 'Dépenses ('. currency()->symbol .')',
                    'data' => $expenses,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
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
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString() + " '. currency()->symbol .'"; }',
                    ],
                ],
            ],
        ];
    }
}
