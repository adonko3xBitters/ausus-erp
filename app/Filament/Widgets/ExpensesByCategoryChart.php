<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpensesByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Répartition des Dépenses (Mois en cours)';
    protected static ?int $sort = 6;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $expenses = Expense::query()
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Dépenses ('. currency()->symbol .')',
                    'data' => $expenses->pluck('total')->toArray(),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                    ],
                ],
            ],
            'labels' => $expenses->pluck('category.name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}
