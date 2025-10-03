<?php

namespace App\Filament\Pages;

use App\Models\FiscalYear;
use App\Services\ReportService;
use Filament\Pages\Page;
use Filament\Actions\Action;

class BalanceSheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Rapports';

    protected static ?string $navigationLabel = 'Bilan comptable';

    protected static string $view = 'filament.pages.balance-sheet';

    protected static ?int $navigationSort = 4;

    public ?string $date = null;
    public ?array $reportData = null;

    public function mount(): void
    {
        $fiscalYear = FiscalYear::getActive();
        $this->date = $fiscalYear
            ? $fiscalYear->end_date->format('Y-m-d')
            : now()->format('Y-m-d');

        $this->loadReport();
    }

    public function loadReport(): void
    {
        $reportService = app(ReportService::class);
        $this->reportData = $reportService->generateBalanceSheet($this->date);
    }

    public function updatedDate(): void
    {
        $this->loadReport();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action('loadReport'),

            Action::make('export_pdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('exportPdf'),
        ];
    }

    public function exportPdf(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Fonctionnalité à venir')
            ->warning()
            ->send();
    }
}
