<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
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
        ];
    }
}
