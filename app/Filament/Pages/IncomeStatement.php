<?php

namespace App\Filament\Pages;

use App\Models\FiscalYear;
use App\Services\PdfExportService;
use App\Services\ReportService;
use Filament\Pages\Page;
use Filament\Actions\Action;

class IncomeStatement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    protected static ?string $navigationGroup = 'Rapports';

    protected static ?string $navigationLabel = 'Compte de résultat';

    protected static string $view = 'filament.pages.income-statement';

    protected static ?int $navigationSort = 3;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $fiscalYearId = null;
    public ?array $reportData = null;

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

        $this->loadReport();
    }

    public function loadReport(): void
    {
        $reportService = app(ReportService::class);
        $this->reportData = $reportService->generateIncomeStatement(
            $this->startDate,
            $this->endDate
        );
    }

    public function updatedFiscalYearId(): void
    {
        if ($this->fiscalYearId) {
            $fiscalYear = FiscalYear::find($this->fiscalYearId);
            $this->startDate = $fiscalYear->start_date->format('Y-m-d');
            $this->endDate = $fiscalYear->end_date->format('Y-m-d');
        }
        $this->loadReport();
    }

    public function updatedStartDate(): void
    {
        $this->loadReport();
    }

    public function updatedEndDate(): void
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

    public function exportPdf(): \Symfony\Component\HttpFoundation\Response
    {
        $pdfService = app(PdfExportService::class);
        $pdf = $pdfService->generateIncomeStatementPdf($this->reportData);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'compte-resultat-' . now()->format('Y-m-d') . '.pdf');
    }
}
