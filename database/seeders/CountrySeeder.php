<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            // Pays OHADA principaux
            ['code' => 'CI', 'name' => 'Côte d\'Ivoire', 'currency_code' => 'XOF', 'phone_code' => '+225', 'is_ohada' => true],
            ['code' => 'SN', 'name' => 'Sénégal', 'currency_code' => 'XOF', 'phone_code' => '+221', 'is_ohada' => true],
            ['code' => 'BF', 'name' => 'Burkina Faso', 'currency_code' => 'XOF', 'phone_code' => '+226', 'is_ohada' => true],
            ['code' => 'ML', 'name' => 'Mali', 'currency_code' => 'XOF', 'phone_code' => '+223', 'is_ohada' => true],
            ['code' => 'BJ', 'name' => 'Bénin', 'currency_code' => 'XOF', 'phone_code' => '+229', 'is_ohada' => true],
            ['code' => 'TG', 'name' => 'Togo', 'currency_code' => 'XOF', 'phone_code' => '+228', 'is_ohada' => true],
            ['code' => 'NE', 'name' => 'Niger', 'currency_code' => 'XOF', 'phone_code' => '+227', 'is_ohada' => true],
            ['code' => 'GW', 'name' => 'Guinée-Bissau', 'currency_code' => 'XOF', 'phone_code' => '+245', 'is_ohada' => true],
            ['code' => 'CM', 'name' => 'Cameroun', 'currency_code' => 'XAF', 'phone_code' => '+237', 'is_ohada' => true],
            ['code' => 'GA', 'name' => 'Gabon', 'currency_code' => 'XAF', 'phone_code' => '+241', 'is_ohada' => true],
            ['code' => 'CG', 'name' => 'Congo', 'currency_code' => 'XAF', 'phone_code' => '+242', 'is_ohada' => true],
            ['code' => 'CD', 'name' => 'RD Congo', 'currency_code' => 'CDF', 'phone_code' => '+243', 'is_ohada' => true],
            ['code' => 'CF', 'name' => 'Centrafrique', 'currency_code' => 'XAF', 'phone_code' => '+236', 'is_ohada' => true],
            ['code' => 'TD', 'name' => 'Tchad', 'currency_code' => 'XAF', 'phone_code' => '+235', 'is_ohada' => true],
            ['code' => 'GQ', 'name' => 'Guinée Équatoriale', 'currency_code' => 'XAF', 'phone_code' => '+240', 'is_ohada' => true],
            ['code' => 'GN', 'name' => 'Guinée', 'currency_code' => 'GNF', 'phone_code' => '+224', 'is_ohada' => true],
            ['code' => 'KM', 'name' => 'Comores', 'currency_code' => 'KMF', 'phone_code' => '+269', 'is_ohada' => true],

            // Autres pays importants
            ['code' => 'FR', 'name' => 'France', 'currency_code' => 'EUR', 'phone_code' => '+33', 'is_ohada' => false],
            ['code' => 'US', 'name' => 'États-Unis', 'currency_code' => 'USD', 'phone_code' => '+1', 'is_ohada' => false],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}
