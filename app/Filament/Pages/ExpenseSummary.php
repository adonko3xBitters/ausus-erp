<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExpenseStatsOverview;
use App\Models\Expense;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ExpenseSummary extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-c-presentation-chart-line';

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
        return [
            ExpenseStatsOverview::make([
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
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
