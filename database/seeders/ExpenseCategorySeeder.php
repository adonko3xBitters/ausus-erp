<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Account;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Catégories principales
            [
                'name' => 'Frais de personnel',
                'description' => 'Salaires, charges sociales, formation...',
                'account_code' => '66',
                'children' => [
                    ['name' => 'Salaires et traitements', 'account_code' => '661'],
                    ['name' => 'Charges sociales', 'account_code' => '664'],
                    ['name' => 'Formation du personnel', 'account_code' => '625'],
                ],
            ],
            [
                'name' => 'Loyers et charges locatives',
                'description' => 'Loyer, charges de copropriété...',
                'account_code' => '622',
                'children' => [
                    ['name' => 'Loyer bureau', 'account_code' => '6221'],
                    ['name' => 'Charges locatives', 'account_code' => '6222'],
                ],
            ],
            [
                'name' => 'Eau, électricité, téléphone',
                'description' => 'Services publics',
                'account_code' => '625',
                'children' => [
                    ['name' => 'Électricité', 'account_code' => '6251'],
                    ['name' => 'Eau', 'account_code' => '6252'],
                    ['name' => 'Téléphone et internet', 'account_code' => '6253'],
                ],
            ],
            [
                'name' => 'Assurances',
                'description' => 'Primes d\'assurance',
                'account_code' => '625',
            ],
            [
                'name' => 'Fournitures de bureau',
                'description' => 'Papeterie, consommables...',
                'account_code' => '604',
            ],
            [
                'name' => 'Entretien et maintenance',
                'description' => 'Réparations, maintenance...',
                'account_code' => '623',
                'children' => [
                    ['name' => 'Entretien des locaux', 'account_code' => '6231'],
                    ['name' => 'Maintenance informatique', 'account_code' => '6232'],
                    ['name' => 'Maintenance véhicules', 'account_code' => '6233'],
                ],
            ],
            [
                'name' => 'Carburant',
                'description' => 'Essence, gasoil...',
                'account_code' => '624',
            ],
            [
                'name' => 'Frais de déplacement',
                'description' => 'Missions, voyages...',
                'account_code' => '625',
                'children' => [
                    ['name' => 'Transport', 'account_code' => '6251'],
                    ['name' => 'Hébergement', 'account_code' => '6252'],
                    ['name' => 'Restauration', 'account_code' => '6253'],
                ],
            ],
            [
                'name' => 'Services bancaires',
                'description' => 'Frais bancaires, commissions...',
                'account_code' => '627',
            ],
            [
                'name' => 'Marketing et publicité',
                'description' => 'Communication, publicité...',
                'account_code' => '623',
            ],
            [
                'name' => 'Services professionnels',
                'description' => 'Honoraires, conseils...',
                'account_code' => '628',
                'children' => [
                    ['name' => 'Honoraires comptables', 'account_code' => '6281'],
                    ['name' => 'Honoraires juridiques', 'account_code' => '6282'],
                    ['name' => 'Honoraires autres', 'account_code' => '6283'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $account = Account::where('code', $categoryData['account_code'])->first();

            if (!$account) {
                continue;
            }

            $category = ExpenseCategory::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'] ?? null,
                'account_id' => $account->id,
                'is_active' => true,
            ]);

            // Créer les sous-catégories
            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $childData) {
                    $childAccount = Account::where('code', $childData['account_code'])->first();

                    if ($childAccount) {
                        ExpenseCategory::create([
                            'name' => $childData['name'],
                            'account_id' => $childAccount->id,
                            'parent_id' => $category->id,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }
}
