<?php

namespace App\Filament\Pages;

use App\Models\FiscalYear;
use App\Services\ReportService;
use Filament\Pages\Page;
use Filament\Actions\Action;

class CashFlow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Rapports';

    protected static ?string $navigationLabel = 'Flux de trésorerie';

    protected static string $view = 'filament.pages.cash-flow';

    protected static ?int $navigationSort = 5;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?array $reportData = null;

    public function mount(): void
    {
        $fiscalYear = FiscalYear::getActive();

        if ($fiscalYear) {
            $this->startDate = $fiscalYear->start_date->format('Y-m-d');
            $this->endDate = $fiscalYear->end_date->format('Y-m-d');
        } else {
            $this->startDate = now()->startOfMonth()->format('Y-m-d');
            $this->endDate = now()->endOfMonth()->format('Y-m-d');
        }

        $this->loadReport();
    }

    public function loadReport(): void
    {
        $reportService = app(ReportService::class);
        $this->reportData = $reportService->generateCashFlowStatement(
            $this->startDate,
            $this->endDate
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action('loadReport'),
        ];
    }
}
