<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Générer la balance générale
     */
    public function generateTrialBalance(?string $startDate = null, ?string $endDate = null, ?int $fiscalYearId = null): array
    {
        // Utiliser l'exercice actif si non spécifié
        if (!$startDate || !$endDate) {
            $fiscalYear = $fiscalYearId
                ? FiscalYear::find($fiscalYearId)
                : FiscalYear::getActive();

            if ($fiscalYear) {
                $startDate = $fiscalYear->start_date->format('Y-m-d');
                $endDate = $fiscalYear->end_date->format('Y-m-d');
            }
        }

        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();

        $balance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            // Calculer les mouvements débit et crédit
            $movements = Transaction::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->selectRaw('
                    SUM(debit) as total_debit,
                    SUM(credit) as total_credit
                ')
                ->first();

            $debit = (float) ($movements->total_debit ?? 0);
            $credit = (float) ($movements->total_credit ?? 0);

            // Ne garder que les comptes avec des mouvements
            if ($debit > 0 || $credit > 0) {
                $balance[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->type,
                    'account_category' => $account->category,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $debit - $credit,
                ];

                $totalDebit += $debit;
                $totalCredit += $credit;
            }
        }

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'accounts' => $balance,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'difference' => $totalDebit - $totalCredit,
            ],
        ];
    }

    /**
     * Générer le grand livre d'un compte
     */
    public function generateAccountLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = Account::findOrFail($accountId);

        // Utiliser l'exercice actif si non spécifié
        if (!$startDate || !$endDate) {
            $fiscalYear = FiscalYear::getActive();
            if ($fiscalYear) {
                $startDate = $fiscalYear->start_date->format('Y-m-d');
                $endDate = $fiscalYear->end_date->format('Y-m-d');
            }
        }

        // Calculer le solde initial (avant la période)
        $previousBalance = Transaction::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->where('status', 'posted')
                    ->where('date', '<', $startDate);
            })
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance') ?? 0;

        // Récupérer les transactions de la période
        $transactions = Transaction::with(['journalEntry.journal'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')
                    ->whereBetween('date', [$startDate, $endDate]);
            })
            ->orderBy('id')
            ->get();

        $ledger = [];
        $balance = (float) $previousBalance;

        foreach ($transactions as $transaction) {
            $balance += ($transaction->debit - $transaction->credit);

            $ledger[] = [
                'date' => $transaction->journalEntry->date->format('d/m/Y'),
                'entry_number' => $transaction->journalEntry->entry_number,
                'journal' => $transaction->journalEntry->journal->name,
                'description' => $transaction->description ?? $transaction->journalEntry->description,
                'reference' => $transaction->journalEntry->reference,
                'debit' => (float) $transaction->debit,
                'credit' => (float) $transaction->credit,
                'balance' => $balance,
            ];
        }

        return [
            'account' => [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'previous_balance' => $previousBalance,
            'transactions' => $ledger,
            'final_balance' => $balance,
        ];
    }

    /**
     * Générer le compte de résultat (Classe 6 et 7)
     */
    public function generateIncomeStatement(?string $startDate = null, ?string $endDate = null): array
    {
        // Utiliser l'exercice actif si non spécifié
        if (!$startDate || !$endDate) {
            $fiscalYear = FiscalYear::getActive();
            if ($fiscalYear) {
                $startDate = $fiscalYear->start_date->format('Y-m-d');
                $endDate = $fiscalYear->end_date->format('Y-m-d');
            }
        }

        // Charges (Classe 6)
        $expenses = $this->getAccountsByClass('class_6', $startDate, $endDate);
        $totalExpenses = collect($expenses)->sum('balance');

        // Produits (Classe 7)
        $revenues = $this->getAccountsByClass('class_7', $startDate, $endDate);
        $totalRevenues = collect($revenues)->sum('balance');

        // Résultat net
        $netIncome = abs($totalRevenues) - abs($totalExpenses);

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'expenses' => [
                'accounts' => $expenses,
                'total' => abs($totalExpenses),
            ],
            'revenues' => [
                'accounts' => $revenues,
                'total' => abs($totalRevenues),
            ],
            'net_income' => $netIncome,
        ];
    }

    /**
     * Générer le bilan (Classes 1 à 5)
     */
    public function generateBalanceSheet(?string $date = null): array
    {
        // Utiliser la date de fin de l'exercice actif si non spécifiée
        if (!$date) {
            $fiscalYear = FiscalYear::getActive();
            $date = $fiscalYear ? $fiscalYear->end_date->format('Y-m-d') : now()->format('Y-m-d');
        }

        $startDate = now()->parse($date)->startOfYear()->format('Y-m-d');

        // ACTIF
        // Actif immobilisé (Classe 2)
        $fixedAssets = $this->getAccountsByClass('class_2', $startDate, $date);
        $totalFixedAssets = collect($fixedAssets)->sum('balance');

        // Actif circulant (Classes 3, 4, 5)
        $stocks = $this->getAccountsByClass('class_3', $startDate, $date);
        $receivables = $this->getAccountsByCategory('class_4', ['asset'], $startDate, $date);
        $cash = $this->getAccountsByClass('class_5', $startDate, $date);

        $currentAssets = array_merge($stocks, $receivables, $cash);
        $totalCurrentAssets = collect($currentAssets)->sum('balance');

        $totalAssets = $totalFixedAssets + $totalCurrentAssets;

        // PASSIF
        // Capitaux propres (Classe 1)
        $equity = $this->getAccountsByCategory('class_1', ['equity'], $startDate, $date);
        $totalEquity = collect($equity)->sum('balance');

        // Dettes (Classes 1, 4)
        $longTermDebt = $this->getAccountsByCategory('class_1', ['liability'], $startDate, $date);
        $currentLiabilities = $this->getAccountsByCategory('class_4', ['liability'], $startDate, $date);

        $liabilities = array_merge($longTermDebt, $currentLiabilities);
        $totalLiabilities = collect($liabilities)->sum('balance');

        $totalEquityAndLiabilities = abs($totalEquity) + abs($totalLiabilities);

        return [
            'date' => $date,
            'assets' => [
                'fixed_assets' => [
                    'accounts' => $fixedAssets,
                    'total' => $totalFixedAssets,
                ],
                'current_assets' => [
                    'accounts' => $currentAssets,
                    'total' => $totalCurrentAssets,
                ],
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'equity' => [
                    'accounts' => $equity,
                    'total' => abs($totalEquity),
                ],
                'liabilities' => [
                    'accounts' => $liabilities,
                    'total' => abs($totalLiabilities),
                ],
                'total' => $totalEquityAndLiabilities,
            ],
        ];
    }

    /**
     * Récupérer les comptes par classe
     */
    protected function getAccountsByClass(string $class, string $startDate, string $endDate): array
    {
        $accounts = Account::where('category', $class)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $result = [];

        foreach ($accounts as $account) {
            $movements = Transaction::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->value('balance') ?? 0;

            if ($movements != 0) {
                $result[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => (float) $movements,
                ];
            }
        }

        return $result;
    }

    /**
     * Récupérer les comptes par catégorie et type
     */
    protected function getAccountsByCategory(string $category, array $types, string $startDate, string $endDate): array
    {
        $accounts = Account::where('category', $category)
            ->whereIn('type', $types)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $result = [];

        foreach ($accounts as $account) {
            $movements = Transaction::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                        ->whereBetween('date', [$startDate, $endDate]);
                })
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->value('balance') ?? 0;

            if ($movements != 0) {
                $result[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => (float) $movements,
                ];
            }
        }

        return $result;
    }

    /**
     * Générer un rapport de flux de trésorerie
     */
    public function generateCashFlowStatement(?string $startDate = null, ?string $endDate = null): array
    {
        // Utiliser l'exercice actif si non spécifié
        if (!$startDate || !$endDate) {
            $fiscalYear = FiscalYear::getActive();
            if ($fiscalYear) {
                $startDate = $fiscalYear->start_date->format('Y-m-d');
                $endDate = $fiscalYear->end_date->format('Y-m-d');
            }
        }

        // Comptes de trésorerie (Classe 5)
        $cashAccounts = $this->getAccountsByClass('class_5', $startDate, $endDate);

        // Solde initial
        $initialBalance = Transaction::whereIn('account_id', function ($query) {
            $query->select('id')
                ->from('accounts')
                ->where('category', 'class_5');
        })
            ->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->where('status', 'posted')
                    ->where('date', '<', $startDate);
            })
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->value('balance') ?? 0;

        // Flux de la période
        $cashFlow = collect($cashAccounts)->sum('balance');

        // Solde final
        $finalBalance = $initialBalance + $cashFlow;

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'initial_balance' => (float) $initialBalance,
            'cash_flow' => $cashFlow,
            'final_balance' => $finalBalance,
            'accounts' => $cashAccounts,
        ];
    }
}
