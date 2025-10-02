<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\FiscalYear;
use App\Services\ReportService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Actions\Action;

class AccountLedger extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Rapports';

    protected static ?string $navigationLabel = 'Grand livre';

    protected static string $view = 'filament.pages.account-ledger';

    protected static ?int $navigationSort = 2;

    public ?int $accountId = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?array $ledgerData = null;

    public function mount(): void
    {
        $fiscalYear = FiscalYear::getActive();

        if ($fiscalYear) {
            $this->startDate = $fiscalYear->start_date->format('Y-m-d');
            $this->endDate = $fiscalYear->end_date->format('Y-m-d');
        }
    }

    public function loadLedger(): void
    {
        if (!$this->accountId) {
            $this->ledgerData = null;
            return;
        }

        $reportService = app(ReportService::class);
        $this->ledgerData = $reportService->generateAccountLedger(
            $this->accountId,
            $this->startDate,
            $this->endDate
        );
    }

    public function updatedAccountId(): void
    {
        $this->loadLedger();
    }

    public function updatedStartDate(): void
    {
        $this->loadLedger();
    }

    public function updatedEndDate(): void
    {
        $this->loadLedger();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->disabled(fn () => !$this->ledgerData)
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
