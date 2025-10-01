<?php

namespace Database\Seeders;

use App\Models\Journal;
use App\Models\Account;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer des comptes par défaut
        $clientAccount = Account::where('code', '411')->first();
        $salesAccount = Account::where('code', '701')->first();
        $supplierAccount = Account::where('code', '401')->first();
        $purchaseAccount = Account::where('code', '601')->first();
        $bankAccount = Account::where('code', '521')->first();
        $cashAccount = Account::where('code', '571')->first();

        $journals = [
            [
                'code' => 'VT',
                'name' => 'Journal des ventes',
                'type' => 'sales',
                'description' => 'Enregistrement des factures clients et ventes',
                'default_debit_account_id' => $clientAccount?->id,
                'default_credit_account_id' => $salesAccount?->id,
            ],
            [
                'code' => 'AC',
                'name' => 'Journal des achats',
                'type' => 'purchases',
                'description' => 'Enregistrement des factures fournisseurs et achats',
                'default_debit_account_id' => $purchaseAccount?->id,
                'default_credit_account_id' => $supplierAccount?->id,
            ],
            [
                'code' => 'BQ',
                'name' => 'Journal de banque',
                'type' => 'bank',
                'description' => 'Mouvements bancaires (virements, prélèvements...)',
                'default_debit_account_id' => $bankAccount?->id,
                'default_credit_account_id' => null,
            ],
            [
                'code' => 'CA',
                'name' => 'Journal de caisse',
                'type' => 'cash',
                'description' => 'Mouvements de caisse (espèces)',
                'default_debit_account_id' => $cashAccount?->id,
                'default_credit_account_id' => null,
            ],
            [
                'code' => 'OD',
                'name' => 'Journal des opérations diverses',
                'type' => 'general',
                'description' => 'Écritures diverses (salaires, charges, régularisations...)',
                'default_debit_account_id' => null,
                'default_credit_account_id' => null,
            ],
        ];

        foreach ($journals as $journal) {
            Journal::create($journal);
        }
    }
}
