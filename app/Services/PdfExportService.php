<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CompanySetting;

class PdfExportService
{
    /**
     * Générer un PDF de la balance générale
     */
    public function generateTrialBalancePdf(array $data): \Barryvdh\DomPDF\PDF
    {
        $company = CompanySetting::first();

        return Pdf::loadView('pdf.trial-balance', [
            'company' => $company,
            'data' => $data,
        ])
            ->setPaper('a4', 'landscape');
    }

    /**
     * Générer un PDF du grand livre
     */
    public function generateLedgerPdf(array $data): \Barryvdh\DomPDF\PDF
    {
        $company = CompanySetting::first();

        return Pdf::loadView('pdf.ledger', [
            'company' => $company,
            'data' => $data,
        ]);
    }

    /**
     * Générer un PDF du compte de résultat
     */
    public function generateIncomeStatementPdf(array $data): \Barryvdh\DomPDF\PDF
    {
        $company = CompanySetting::first();

        return Pdf::loadView('pdf.income-statement', [
            'company' => $company,
            'data' => $data,
        ]);
    }

    /**
     * Générer un PDF du bilan
     */
    public function generateBalanceSheetPdf(array $data): \Barryvdh\DomPDF\PDF
    {
        $company = CompanySetting::first();

        return Pdf::loadView('pdf.balance-sheet', [
            'company' => $company,
            'data' => $data,
        ]);
    }
}
