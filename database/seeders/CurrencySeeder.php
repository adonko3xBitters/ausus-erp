<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'XOF', 'name' => 'Franc CFA (BCEAO)', 'symbol' => 'FCFA', 'decimal_places' => 0, 'is_base' => true],
            ['code' => 'XAF', 'name' => 'Franc CFA (BEAC)', 'symbol' => 'FCFA', 'decimal_places' => 0, 'is_base' => false],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'is_base' => false],
            ['code' => 'USD', 'name' => 'Dollar américain', 'symbol' => '$', 'decimal_places' => 2, 'is_base' => false],
            ['code' => 'GNF', 'name' => 'Franc guinéen', 'symbol' => 'FG', 'decimal_places' => 0, 'is_base' => false],
            ['code' => 'CDF', 'name' => 'Franc congolais', 'symbol' => 'FC', 'decimal_places' => 2, 'is_base' => false],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }
}
