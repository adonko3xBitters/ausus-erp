<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingSeeder extends Seeder
{
    public function run(): void
    {
        CompanySetting::create([
            'company_name' => 'Ma Société SARL',
            'legal_form' => 'SARL',
            'country_id' => 1, // Côte d'Ivoire
            'currency_id' => 1, // XOF
            'fiscal_regime' => 'Réel Normal',
            'city' => 'Abidjan',
        ]);
    }
}
