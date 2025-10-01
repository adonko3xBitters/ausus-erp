<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Créer une écriture comptable
     */
    public function createEntry(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            // Récupérer le journal
            $journal = Journal::findOrFail($data['journal_id']);

            // Générer le numéro d'écriture
            $entryNumber = $data['entry_number'] ?? $journal->getNextEntryNumber();

            // Devise par défaut
            $currency = Currency::where('is_base', true)->first();

            // Créer l'écriture
            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'journal_id' => $journal->id,
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
                'currency_id' => $data['currency_id'] ?? $currency->id,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'status' => $data['status'] ?? 'draft',
                'entryable_type' => $data['entryable_type'] ?? null,
                'entryable_id' => $data['entryable_id'] ?? null,
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Créer les lignes d'écriture
            if (isset($data['transactions']) && is_array($data['transactions'])) {
                foreach ($data['transactions'] as $transactionData) {
                    $this->addTransaction($entry, $transactionData);
                }
            }

            // Calculer les totaux
            $entry->calculateTotals();

            return $entry->fresh('transactions');
        });
    }

    /**
     * Ajouter une ligne d'écriture
     */
    public function addTransaction(JournalEntry $entry, array $data): Transaction
    {
        $transaction = $entry->transactions()->create([
            'account_id' => $data['account_id'],
            'debit' => $data['debit'] ?? 0,
            'credit' => $data['credit'] ?? 0,
            'description' => $data['description'] ?? null,
            'transactionable_type' => $data['transactionable_type'] ?? null,
            'transactionable_id' => $data['transactionable_id'] ?? null,
        ]);

        // Recalculer les totaux
        $entry->calculateTotals();

        return $transaction;
    }

    /**
     * Créer une écriture simple (2 lignes)
     */
    public function createSimpleEntry(
        int $journalId,
        string $date,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $description,
        ?string $reference = null,
        ?array $options = []
    ): JournalEntry {
        return $this->createEntry([
            'journal_id' => $journalId,
            'date' => $date,
            'reference' => $reference,
            'description' => $description,
            'transactions' => [
                [
                    'account_id' => $debitAccountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => $description,
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => $description,
                ],
            ],
            ...$options,
        ]);
    }

    /**
     * Valider une écriture
     */
    public function postEntry(JournalEntry $entry): bool
    {
        if (!$entry->isBalanced()) {
            throw new \Exception('L\'écriture n\'est pas équilibrée');
        }

        return $entry->post();
    }

    /**
     * Annuler une écriture (créer une écriture d'extourne)
     */
    public function reverseEntry(JournalEntry $entry): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new \Exception('Seules les écritures validées peuvent être extournées');
        }

        return DB::transaction(function () use ($entry) {
            // Créer une écriture inverse
            $reversedEntry = $this->createEntry([
                'journal_id' => $entry->journal_id,
                'date' => now()->format('Y-m-d'),
                'reference' => 'EXTOURNE-' . $entry->entry_number,
                'description' => 'Extourne de : ' . $entry->description,
                'currency_id' => $entry->currency_id,
                'exchange_rate' => $entry->exchange_rate,
                'transactions' => $entry->transactions->map(function ($transaction) {
                    return [
                        'account_id' => $transaction->account_id,
                        'debit' => $transaction->credit, // Inverse
                        'credit' => $transaction->debit, // Inverse
                        'description' => $transaction->description,
                    ];
                })->toArray(),
            ]);

            // Valider l'écriture d'extourne
            $this->postEntry($reversedEntry);

            // Marquer l'écriture originale comme annulée
            $entry->cancel();

            return $reversedEntry;
        });
    }
}
