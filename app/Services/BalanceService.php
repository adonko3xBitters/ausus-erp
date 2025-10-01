<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\FiscalYear;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Calculer le solde d'un compte pour une période
     */
    public function getAccountBalance(
        int $accountId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = Transaction::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted');
            });

        if ($startDate) {
            $query->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->where('date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate);
            });
        }

        $result = $query->selectRaw('
            SUM(debit) as total_debit,
            SUM(credit) as total_credit
        ')->first();

        $debit = (float) ($result->total_debit ?? 0);
        $credit = (float) ($result->total_credit ?? 0);
        $balance = $debit - $credit;

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $balance,
        ];
    }

    /**
     * Générer la balance générale
     */
    public function generateTrialBalance(?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = Account::where('is_active', true)
            ->orderBy('code')
            ->get();

        $balance = [];

        foreach ($accounts as $account) {
            $accountBalance = $this->getAccountBalance($account->id, $startDate, $endDate);

            // Ne garder que les comptes avec des mouvements
            if ($accountBalance['debit'] > 0 || $accountBalance['credit'] > 0) {
                $balance[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'debit' => $accountBalance['debit'],
                    'credit' => $accountBalance['credit'],
                    'balance' => $accountBalance['balance'],
                ];
            }
        }

        return $balance;
    }

    /**
     * Mettre à jour les soldes dans la table account_balances
     */
    public function updateAccountBalances(FiscalYear $fiscalYear): void
    {
        DB::transaction(function () use ($fiscalYear) {
            $accounts = Account::where('is_active', true)->get();

            foreach ($accounts as $account) {
                $balance = $this->getAccountBalance(
                    $account->id,
                    $fiscalYear->start_date->format('Y-m-d'),
                    $fiscalYear->end_date->format('Y-m-d')
                );

                AccountBalance::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'date' => $fiscalYear->end_date,
                    ],
                    [
                        'debit' => $balance['debit'],
                        'credit' => $balance['credit'],
                        'balance' => $balance['balance'],
                    ]
                );
            }
        });
    }

    /**
     * Obtenir le grand livre d'un compte
     */
    public function getAccountLedger(
        int $accountId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = Transaction::with(['journalEntry.journal'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted');
            });

        if ($startDate) {
            $query->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->where('date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->where('date', '<=', $endDate);
            });
        }

        $transactions = $query->orderBy('id')->get();

        $ledger = [];
        $balance = 0;

        foreach ($transactions as $transaction) {
            $balance += ($transaction->debit - $transaction->credit);

            $ledger[] = [
                'date' => $transaction->journalEntry->date->format('d/m/Y'),
                'entry_number' => $transaction->journalEntry->entry_number,
                'journal' => $transaction->journalEntry->journal->name,
                'description' => $transaction->description ?? $transaction->journalEntry->description,
                'debit' => $transaction->debit,
                'credit' => $transaction->credit,
                'balance' => $balance,
            ];
        }

        return $ledger;
    }
}
