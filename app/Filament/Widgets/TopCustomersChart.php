<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class TopCustomersChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Clients (Année en cours)';
    protected static ?int $sort = 4;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $topCustomers = Invoice::query()
            ->where('status', 'paid')
            ->whereYear('created_at', now()->year)
            ->select('customer_id', DB::raw('SUM(total) as total'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('customer')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenus (FCFA)',
                    'data' => $topCustomers->pluck('total')->toArray(),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                    ],
                ],
            ],
            'labels' => $topCustomers->pluck('customer.name')->toArray(),
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
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'callback' => 'function(value) { return value.toLocaleString() + " '. currency()->symbol .'"; }',
                    ],
                ],
            ],
        ];
    }
}
