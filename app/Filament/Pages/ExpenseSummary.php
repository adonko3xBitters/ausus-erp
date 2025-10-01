<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ExpenseSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Dépenses';

    protected static ?string $navigationLabel = 'Synthèse des dépenses';

    protected static string $view = 'filament.pages.expense-summary';

    protected static ?int $navigationSort = 3;

    public $startDate;
    public $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    protected function getHeaderWidgets(): array
    {
        $query = Expense::query()
            ->whereBetween('expense_date', [$this->startDate, $this->endDate]);

        return [
            StatsWidget::make([
                'stats' => [
                    Stat::make('Total dépenses', number_format($query->sum('total_amount'), 0) . ' FCFA')
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
                ],
            ]),
        ];
    }

    public function getExpensesByCategory(): array
    {
        return Expense::query()
            ->selectRaw('expense_category_id, SUM(total_amount) as total')
            ->whereBetween('expense_date', [$this->startDate, $this->endDate])
            ->where('status', 'paid')
            ->groupBy('expense_category_id')
            ->with('expenseCategory')
            ->get()
            ->map(fn ($expense) => [
                'category' => $expense->expenseCategory->name,
                'total' => $expense->total,
            ])
            ->toArray();
    }
}
