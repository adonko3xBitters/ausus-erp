<?php

namespace App\Filament\Pages;

use App\Models\FiscalYear;
use App\Services\ReportService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Actions\Action;

class TrialBalance extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Rapports';

    protected static ?string $navigationLabel = 'Balance générale';

    protected static string $view = 'filament.pages.trial-balance';

    protected static ?int $navigationSort = 1;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $fiscalYearId = null;
    public ?array $balanceData = null;

    public function mount(): void
    {
        $fiscalYear = FiscalYear::getActive();

        if ($fiscalYear) {
            $this->fiscalYearId = $fiscalYear->id;
            $this->startDate = $fiscalYear->start_date->format('Y-m-d');
            $this->endDate = $fiscalYear->end_date->format('Y-m-d');
        } else {
            $this->startDate = now()->startOfYear()->format('Y-m-d');
            $this->endDate = now()->endOfYear()->format('Y-m-d');
        }

        $this->loadBalance();
    }

    public function loadBalance(): void
    {
        $reportService = app(ReportService::class);
        $this->balanceData = $reportService->generateTrialBalance(
            $this->startDate,
            $this->endDate,
            $this->fiscalYearId
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action('loadBalance'),

            Action::make('export_pdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('exportPdf'),

            Action::make('export_excel')
                ->label('Exporter Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action('exportExcel'),
        ];
    }

    public function exportPdf(): void
    {
        // TODO: Implémenter l'export PDF
        \Filament\Notifications\Notification::make()
            ->title('Fonctionnalité à venir')
            ->warning()
            ->send();
    }

    public function exportExcel(): void
    {
        // TODO: Implémenter l'export Excel
        \Filament\Notifications\Notification::make()
            ->title('Fonctionnalité à venir')
            ->warning()
            ->send();
    }
}
