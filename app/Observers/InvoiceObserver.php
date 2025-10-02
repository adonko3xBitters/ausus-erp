<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Journal;
use App\Services\AccountingService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

class InvoiceObserver
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
    public function creating(Invoice $invoice): void
    {
        if (empty($invoice->invoice_number)) {
            $invoice->invoice_number = Invoice::generateNumber();
        }

        // Calculer la date d'échéance
        if (empty($invoice->due_date)) {
            $invoice->due_date = $invoice->invoice_date
                ->addDays($invoice->customer->payment_terms ?? 30);
        }
    }

    /**
     * Recalculer les totaux après création
     */
    public function created(Invoice $invoice): void
    {
        // $invoice->calculateTotals();
    }

    /**
     * Générer l'écriture comptable lors de la validation
     */
    public function updated(Invoice $invoice): void
    {
        // Si la facture passe de "draft" à "sent"
        if ($invoice->isDirty('status') && $invoice->status === 'sent' && $invoice->getOriginal('status') === 'draft') {
            $this->generateJournalEntry($invoice);
            $this->processStockOut($invoice);
        }

        // Recalculer les totaux si les items ont changé
        if ($invoice->wasChanged(['subtotal', 'tax_amount', 'total'])) {
            $invoice->updateStatus();
        }
    }

    /**
     * Supprimer l'écriture comptable associée
     */
    public function deleting(Invoice $invoice): void
    {
        // Supprimer l'écriture comptable si elle existe et n'est pas validée
        if ($invoice->journalEntry && $invoice->journalEntry->status === 'draft') {
            $invoice->journalEntry->delete();
        }
    }

    /**
     * Générer l'écriture comptable
     */
    protected function generateJournalEntry(Invoice $invoice): void
    {
        // Ne pas générer si l'écriture existe déjà
        if ($invoice->journalEntry) {
            return;
        }

        DB::transaction(function () use ($invoice) {
            $journal = Journal::where('code', 'VT')->first();

            if (!$journal) {
                throw new \Exception('Journal des ventes (VT) introuvable');
            }

            // Compte client (411)
            $customerAccount = $invoice->customer->account_id
                ?? \App\Models\Account::where('code', '411')->first()->id;

            $transactions = [];

            // Débit : Client (montant TTC)
            $transactions[] = [
                'account_id' => $customerAccount,
                'debit' => $invoice->total,
                'credit' => 0,
                'description' => "Facture {$invoice->invoice_number} - {$invoice->customer->name}",
                'transactionable_type' => get_class($invoice->customer),
                'transactionable_id' => $invoice->customer_id,
            ];

            // Crédit : Vente (montant HT)
            if ($invoice->subtotal > 0) {
                $salesAccount = \App\Models\Account::where('code', '701')->first();
                $transactions[] = [
                    'account_id' => $salesAccount->id,
                    'debit' => 0,
                    'credit' => $invoice->subtotal,
                    'description' => "Vente {$invoice->invoice_number}",
                ];
            }

            // Crédit : TVA collectée
            if ($invoice->tax_amount > 0) {
                $taxAccount = \App\Models\Account::where('code', '4451')->first();
                $transactions[] = [
                    'account_id' => $taxAccount->id,
                    'debit' => 0,
                    'credit' => $invoice->tax_amount,
                    'description' => "TVA collectée {$invoice->invoice_number}",
                ];
            }

            // Créer l'écriture comptable
            $entry = $this->accountingService->createEntry([
                'journal_id' => $journal->id,
                'date' => $invoice->invoice_date->format('Y-m-d'),
                'reference' => $invoice->invoice_number,
                'description' => "Facture client {$invoice->invoice_number} - {$invoice->customer->name}",
                'currency_id' => $invoice->currency_id,
                'exchange_rate' => $invoice->exchange_rate,
                'entryable_type' => get_class($invoice),
                'entryable_id' => $invoice->id,
                'transactions' => $transactions,
                'status' => 'draft', // En brouillon, à valider manuellement
            ]);

            // Valider automatiquement l'écriture (optionnel)
            // $this->accountingService->postEntry($entry);
        });
    }

    /**
     * Traiter les sorties de stock
     */
    protected function processStockOut(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                // Ne traiter que les produits (pas les services)
                if (!$item->product || $item->product->type !== 'product' || !$item->product->track_inventory) {
                    continue;
                }

                // Obtenir l'entrepôt par défaut
                $warehouse = \App\Models\Warehouse::getDefault();

                if (!$warehouse) {
                    throw new \Exception('Aucun entrepôt par défaut configuré');
                }

                // Vérifier la disponibilité
                $isAvailable = $this->stockService->checkAvailability(
                    $warehouse->id,
                    $item->product_id,
                    $item->quantity,
                    $item->product_variant_id ?? null
                );

                if (!$isAvailable) {
                    throw new \Exception(
                        "Stock insuffisant pour le produit: {$item->product->name}"
                    );
                }

                // Sortie de stock
                $this->stockService->stockOut([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id ?? null,
                    'quantity' => $item->quantity,
                    'cost_per_unit' => $item->product->cost_price,
                    'movement_date' => $invoice->invoice_date->format('Y-m-d'),
                    'reference' => $invoice->invoice_number,
                    'notes' => "Vente - Facture {$invoice->invoice_number}",
                    'movable_type' => get_class($invoice),
                    'movable_id' => $invoice->id,
                ]);
            }
        });
    }
}
