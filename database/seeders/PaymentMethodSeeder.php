<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Account;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $cashAccount = Account::where('code', '571')->first();
        $bankAccount = Account::where('code', '521')->first();

        $methods = [
            [
                'name' => 'Espèces',
                'code' => 'CASH',
                'description' => 'Paiement en espèces',
                'default_account_id' => $cashAccount?->id,
            ],
            [
                'name' => 'Virement bancaire',
                'code' => 'TRANSFER',
                'description' => 'Virement bancaire',
                'default_account_id' => $bankAccount?->id,
            ],
            [
                'name' => 'Chèque',
                'code' => 'CHECK',
                'description' => 'Paiement par chèque',
                'default_account_id' => $bankAccount?->id,
            ],
            [
                'name' => 'Mobile Money',
                'code' => 'MOBILE',
                'description' => 'Orange Money, MTN Mobile Money, Moov Money...',
                'default_account_id' => $cashAccount?->id,
            ],
            [
                'name' => 'Carte bancaire',
                'code' => 'CARD',
                'description' => 'Paiement par carte bancaire',
                'default_account_id' => $bankAccount?->id,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}
