<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Journal;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

class PaymentObserver
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Générer le numéro de paiement
     */
    public function creating(Payment $payment): void
    {
        if (empty($payment->payment_number)) {
            $payment->payment_number = Payment::generateNumber();
        }
    }

    /**
     * Générer l'écriture comptable et mettre à jour la facture
     */
    public function created(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            // Mettre à jour le montant payé de la facture
            $paymentable = $payment->paymentable ?: get_class($payment);
            $paymentable->amount_paid += $payment->amount;
            $paymentable->amount_due = $paymentable->total - $paymentable->amount_paid;
            $paymentable->updateStatus();
            $paymentable->save();

            // Générer l'écriture comptable
            $this->generateJournalEntry($payment);
        });
    }

    /**
     * Supprimer l'écriture comptable et restaurer le montant
     */
    public function deleting(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            // Restaurer le montant de la facture
            $paymentable = $payment->paymentable;
            if ($paymentable) {
                $paymentable->amount_paid -= $payment->amount;
                $paymentable->amount_due = $paymentable->total - $paymentable->amount_paid;
                $paymentable->updateStatus();
                $paymentable->save();
            }

            // Supprimer l'écriture comptable
            if ($payment->journalEntry && $payment->journalEntry->status === 'draft') {
                $payment->journalEntry->delete();
            }
        });
    }

    /**
     * Générer l'écriture comptable
     */
    protected function generateJournalEntry(Payment $payment): void
    {
        if ($payment->journalEntry) {
            return;
        }

        $paymentable = $payment->paymentable;
        $isInvoice = $paymentable instanceof \App\Models\Invoice;

        // Déterminer le journal (Banque ou Caisse)
        $journalCode = $payment->paymentMethod->code === 'CASH' ? 'CA' : 'BQ';
        $journal = Journal::where('code', $journalCode)->first();

        if (!$journal) {
            throw new \Exception("Journal {$journalCode} introuvable");
        }

        $transactions = [];

        if ($isInvoice) {
            // Paiement client : Débit Banque/Caisse, Crédit Client
            $transactions[] = [
                'account_id' => $payment->account_id, // Banque ou Caisse
                'debit' => $payment->amount,
                'credit' => 0,
                'description' => "Règlement {$payment->payment_number}",
            ];

            $customerAccount = $paymentable->customer->account_id
                ?? \App\Models\Account::where('code', '411')->first()->id;

            $transactions[] = [
                'account_id' => $customerAccount,
                'debit' => 0,
                'credit' => $payment->amount,
                'description' => "Règlement facture {$paymentable->invoice_number}",
                'transactionable_type' => get_class($paymentable->customer),
                'transactionable_id' => $paymentable->customer_id,
            ];
        } else {
            // Paiement fournisseur : Débit Fournisseur, Crédit Banque/Caisse
            $vendorAccount = $paymentable->vendor->account_id
                ?? \App\Models\Account::where('code', '401')->first()->id;

            $transactions[] = [
                'account_id' => $vendorAccount,
                'debit' => $payment->amount,
                'credit' => 0,
                'description' => "Règlement facture {$paymentable->bill_number}",
                'transactionable_type' => get_class($paymentable->vendor),
                'transactionable_id' => $paymentable->vendor_id,
            ];

            $transactions[] = [
                'account_id' => $payment->account_id, // Banque ou Caisse
                'debit' => 0,
                'credit' => $payment->amount,
                'description' => "Règlement {$payment->payment_number}",
            ];
        }

        // Créer l'écriture comptable
        $this->accountingService->createEntry([
            'journal_id' => $journal->id,
            'date' => $payment->payment_date->format('Y-m-d'),
            'reference' => $payment->payment_number,
            'description' => "Paiement {$payment->payment_number} - {$payment->paymentMethod->name}",
            'currency_id' => $payment->currency_id,
            'exchange_rate' => $payment->exchange_rate,
            'entryable_type' => get_class($payment),
            'entryable_id' => $payment->id,
            'transactions' => $transactions,
            'status' => 'draft',
        ]);
    }
}
