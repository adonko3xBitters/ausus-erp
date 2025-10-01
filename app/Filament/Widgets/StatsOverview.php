<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Country;
use App\Models\Currency;
use App\Models\FiscalYear;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeFiscalYear = FiscalYear::where('is_active', true)->first();

        return [
            Stat::make('Comptes comptables', Account::count())
                ->description('Plan comptable OHADA')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('success'),

            Stat::make('Exercice actif', $activeFiscalYear?->name ?? 'Aucun')
                ->description($activeFiscalYear ? $activeFiscalYear->start_date->format('d/m/Y') . ' - ' . $activeFiscalYear->end_date->format('d/m/Y') : 'Créer un exercice')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Devises', Currency::where('is_active', true)->count())
                ->description('Devises actives')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'),
        ];
    }
}
