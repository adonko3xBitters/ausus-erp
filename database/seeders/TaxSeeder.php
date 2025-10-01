<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $taxes = [
            [
                'name' => 'TVA 18%',
                'code' => 'TVA_18',
                'type' => 'percentage',
                'rate' => 18.0000,
                'description' => 'Taxe sur la Valeur Ajoutée - Taux normal',
                'is_active' => true,
            ],
            [
                'name' => 'AIB 1%',
                'code' => 'AIB_1',
                'type' => 'percentage',
                'rate' => 1.0000,
                'description' => 'Avance sur Impôt sur les Bénéfices',
                'is_active' => true,
            ],
            [
                'name' => 'TSR 5%',
                'code' => 'TSR_5',
                'type' => 'percentage',
                'rate' => 5.0000,
                'description' => 'Taxe Spéciale sur les Revenus',
                'is_active' => true,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::create($tax);
        }
    }
}
