<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\Journal;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class ExpenseObserver
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Générer le numéro de dépense
     */
    public function creating(Expense $expense): void
    {
        if (empty($expense->expense_number)) {
            $expense->expense_number = Expense::generateNumber();
        }

        if (empty($expense->created_by)) {
            $expense->created_by = auth()->id();
        }
    }

    /**
     * Générer l'écriture comptable lors du paiement
     */
    public function updated(Expense $expense): void
    {
        // Si la dépense passe de "approved" à "paid"
        if ($expense->isDirty('status') && $expense->status === 'paid' && $expense->getOriginal('status') === 'approved') {
            $this->generateJournalEntry($expense);
        }
    }

    /**
     * Supprimer l'écriture comptable associée
     */
    public function deleting(Expense $expense): void
    {
        if ($expense->journalEntry && $expense->journalEntry->status === 'draft') {
            $expense->journalEntry->delete();
        }
    }

    /**
     * Générer l'écriture comptable
     */
    protected function generateJournalEntry(Expense $expense): void
    {
        // Ne pas générer si l'écriture existe déjà
        if ($expense->journalEntry) {
            return;
        }

        DB::transaction(function () use ($expense) {
            // Déterminer le journal selon le mode de paiement
            $journalCode = $expense->paymentMethod?->code === 'CASH' ? 'CA' : 'BQ';
            $journal = Journal::where('code', $journalCode)->firstOr(function () {
                return Journal::where('code', 'OD')->first();
            });

            if (!$journal) {
                throw new \Exception('Aucun journal trouvé pour enregistrer la dépense');
            }

            $transactions = [];

            // Débit : Compte de charge (montant HT)
            if ($expense->amount > 0) {
                $transactions[] = [
                    'account_id' => $expense->expenseCategory->account_id,
                    'debit' => $expense->amount,
                    'credit' => 0,
                    'description' => $expense->description,
                ];
            }

            // Débit : TVA déductible (si applicable)
            if ($expense->tax_amount > 0 && $expense->tax_id) {
                $taxAccount = \App\Models\Account::where('code', '4452')->first();
                if ($taxAccount) {
                    $transactions[] = [
                        'account_id' => $taxAccount->id,
                        'debit' => $expense->tax_amount,
                        'credit' => 0,
                        'description' => "TVA déductible - {$expense->expense_number}",
                    ];
                }
            }

            // Crédit : Compte bancaire ou caisse (montant TTC)
            $paymentAccount = $expense->account_id ?? \App\Models\Account::where('code', '571')->first()->id;
            $transactions[] = [
                'account_id' => $paymentAccount,
                'debit' => 0,
                'credit' => $expense->total_amount,
                'description' => "Paiement dépense {$expense->expense_number}",
            ];

            // Si un fournisseur est associé, utiliser le compte fournisseur
            if ($expense->vendor_id) {
                // Crédit : Fournisseur
                $vendorAccount = $expense->vendor->account_id
                    ?? \App\Models\Account::where('code', '401')->first()->id;

                $transactions = [
                    [
                        'account_id' => $expense->expenseCategory->account_id,
                        'debit' => $expense->amount,
                        'credit' => 0,
                        'description' => $expense->description,
                    ],
                ];

                if ($expense->tax_amount > 0) {
                    $taxAccount = \App\Models\Account::where('code', '4452')->first();
                    if ($taxAccount) {
                        $transactions[] = [
                            'account_id' => $taxAccount->id,
                            'debit' => $expense->tax_amount,
                            'credit' => 0,
                            'description' => "TVA déductible - {$expense->expense_number}",
                        ];
                    }
                }

                $transactions[] = [
                    'account_id' => $vendorAccount,
                    'debit' => 0,
                    'credit' => $expense->total_amount,
                    'description' => "Facture fournisseur - {$expense->expense_number}",
                    'transactionable_type' => get_class($expense->vendor),
                    'transactionable_id' => $expense->vendor_id,
                ];
            }

            // Créer l'écriture comptable
            $this->accountingService->createEntry([
                'journal_id' => $journal->id,
                'date' => $expense->expense_date->format('Y-m-d'),
                'reference' => $expense->expense_number,
                'description' => "Dépense {$expense->expense_number} - {$expense->description}",
                'currency_id' => $expense->currency_id,
                'exchange_rate' => $expense->exchange_rate,
                'entryable_type' => get_class($expense),
                'entryable_id' => $expense->id,
                'transactions' => $transactions,
                'status' => 'draft', // Brouillon, à valider manuellement
            ]);
        });
    }
}
