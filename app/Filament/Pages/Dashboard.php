<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExpensesByCategoryChart;
use App\Filament\Widgets\FinancialOverview;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\PendingInvoicesWidget;
use App\Filament\Widgets\RevenueExpenseChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopCustomersChart;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-s-arrows-pointing-in';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Tableau de bord';

    protected ?string $heading = 'Tableau de bord';

    public function getSubheading(): ?string
    {
        return greetings() . ' ' . auth()->user()->name . ' 👋, content de vous revoir !';
    }
    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            FinancialOverview::class,
            RevenueExpenseChart::class,
            PendingInvoicesWidget::class,
            TopCustomersChart::class,
            ExpensesByCategoryChart::class,
            LowStockWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}
