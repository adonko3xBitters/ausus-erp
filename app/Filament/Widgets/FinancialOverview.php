<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Bill;
use App\Models\Expense;
use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Revenus du mois
        $monthlyRevenue = Invoice::whereBetween('invoice_date', [$currentMonth, $endOfMonth])
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->sum('total');

        // Dépenses du mois
        $monthlyExpenses = Expense::whereBetween('expense_date', [$currentMonth, $endOfMonth])
            ->where('status', 'paid')
            ->sum('total_amount');

        // Factures impayées
        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->sum('amount_due');

        // Factures fournisseurs à payer
        $unpaidBills = Bill::whereIn('status', ['received', 'partial', 'overdue'])
            ->sum('amount_due');

        return [
            Stat::make('Revenus du mois', number_format($monthlyRevenue, 0) . ' ' . currency()->symbol)
                ->description('Factures clients')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Dépenses du mois', number_format($monthlyExpenses, 0) . ' ' . currency()->symbol)
                ->description('Dépenses payées')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger')
                ->chart([3, 5, 7, 4, 6, 5, 8, 6]),

            Stat::make('Créances clients', number_format($unpaidInvoices, 0) . ' ' . currency()->symbol)
                ->description('À encaisser')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('Dettes fournisseurs', number_format($unpaidBills, 0) . ' ' . currency()->symbol)
                ->description('À payer')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('info'),
        ];
    }
}
