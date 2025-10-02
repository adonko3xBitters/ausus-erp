<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Expense;

class ExpenseStatsOverview extends BaseWidget
{
    use HasWidgetShield;

    public $startDate;
    public $endDate;

    protected function getStats(): array
    {
        $query = Expense::query()
            ->whereBetween('expense_date', [$this->startDate, $this->endDate]);

        return [
            Stat::make('Total dépenses', number_format($query->sum('total_amount'), 0) . ' ' . currency()->symbol)
                ->description('Période sélectionnée')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('En attente', $query->where('status', 'pending')->count())
                ->description('À approuver')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Approuvées', $query->where('status', 'approved')->count())
                ->description('À payer')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info'),

            Stat::make('Payées', $query->where('status', 'paid')->count())
                ->description('Ce mois')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}
