<?php

namespace App\Observers;

use App\Models\Bill;
use App\Models\Journal;
use App\Services\AccountingService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

class BillObserver
{
    protected AccountingService $accountingService;
    protected StockService $stockService;

    public function __construct(
        AccountingService $accountingService,
        StockService $stockService
    )
    {
        $this->accountingService = $accountingService;
        $this->stockService = $stockService;
    }

    /**
     * Générer le numéro de facture
     */
    public function creating(Bill $bill): void
    {
        if (empty($bill->bill_number)) {
            $bill->bill_number = Bill::generateNumber();
        }

        // Calculer la date d'échéance
        if (empty($bill->due_date)) {
            $bill->due_date = $bill->bill_date
                ->addDays($bill->vendor->payment_terms ?? 30);
        }
    }

    /**
     * Recalculer les totaux après création
     */
    public function created(Bill $bill): void
    {
        // $bill->calculateTotals();
    }

    /**
     * Générer l'écriture comptable lors de la validation
     */
    public function updated(Bill $bill): void
    {
        // Si la facture passe de "draft" à "received"
        if ($bill->isDirty('status') && $bill->status === 'received' && $bill->getOriginal('status') === 'draft') {
            $this->generateJournalEntry($bill);
            $this->processStockIn($bill);
        }

        // Recalculer les totaux
        if ($bill->wasChanged(['subtotal', 'tax_amount', 'total'])) {
            $bill->updateStatus();
        }
    }

    /**
     * Supprimer l'écriture comptable associée
     */
    public function deleting(Bill $bill): void
    {
        if ($bill->journalEntry && $bill->journalEntry->status === 'draft') {
            $bill->journalEntry->delete();
        }
    }

    /**
     * Générer l'écriture comptable
     */
    protected function generateJournalEntry(Bill $bill): void
    {
        if ($bill->journalEntry) {
            return;
        }

        DB::transaction(function () use ($bill) {
            $journal = Journal::where('code', 'AC')->first();

            if (!$journal) {
                throw new \Exception('Journal des achats (AC) introuvable');
            }

            // Compte fournisseur (401)
            $vendorAccount = $bill->vendor->account_id
                ?? \App\Models\Account::where('code', '401')->first()->id;

            $transactions = [];

            // Débit : Achat (montant HT)
            if ($bill->subtotal > 0) {
                $purchaseAccount = \App\Models\Account::where('code', '601')->first();
                $transactions[] = [
                    'account_id' => $purchaseAccount->id,
                    'debit' => $bill->subtotal,
                    'credit' => 0,
                    'description' => "Achat {$bill->bill_number}",
                ];
            }

            // Débit : TVA déductible
            if ($bill->tax_amount > 0) {
                $taxAccount = \App\Models\Account::where('code', '4452')->first();
                $transactions[] = [
                    'account_id' => $taxAccount->id,
                    'debit' => $bill->tax_amount,
                    'credit' => 0,
                    'description' => "TVA déductible {$bill->bill_number}",
                ];
            }

            // Crédit : Fournisseur (montant TTC)
            $transactions[] = [
                'account_id' => $vendorAccount,
                'debit' => 0,
                'credit' => $bill->total,
                'description' => "Facture {$bill->bill_number} - {$bill->vendor->name}",
                'transactionable_type' => get_class($bill->vendor),
                'transactionable_id' => $bill->vendor_id,
            ];

            // Créer l'écriture comptable
            $entry = $this->accountingService->createEntry([
                'journal_id' => $journal->id,
                'date' => $bill->bill_date->format('Y-m-d'),
                'reference' => $bill->bill_number,
                'description' => "Facture fournisseur {$bill->bill_number} - {$bill->vendor->name}",
                'currency_id' => $bill->currency_id,
                'exchange_rate' => $bill->exchange_rate,
                'entryable_type' => get_class($bill),
                'entryable_id' => $bill->id,
                'transactions' => $transactions,
                'status' => 'draft',
            ]);
        });
    }

    /**
     * Traiter les entrées de stock
     */
    protected function processStockIn(Bill $bill): void
    {
        DB::transaction(function () use ($bill) {
            foreach ($bill->items as $item) {
                // Ne traiter que les produits (pas les services)
                if (!$item->product || $item->product->type !== 'product' || !$item->product->track_inventory) {
                    continue;
                }

                // Obtenir l'entrepôt par défaut
                $warehouse = \App\Models\Warehouse::getDefault();

                if (!$warehouse) {
                    throw new \Exception('Aucun entrepôt par défaut configuré');
                }

                // Entrée de stock
                $this->stockService->stockIn([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id ?? null,
                    'quantity' => $item->quantity,
                    'cost_per_unit' => $item->unit_price, // Prix d'achat
                    'movement_date' => $bill->bill_date->format('Y-m-d'),
                    'reference' => $bill->bill_number,
                    'notes' => "Achat - Facture {$bill->bill_number}",
                    'movable_type' => get_class($bill),
                    'movable_id' => $bill->id,
                ]);
            }
        });
    }
}
