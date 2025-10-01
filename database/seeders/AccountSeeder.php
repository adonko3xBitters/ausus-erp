<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // CLASSE 1 : COMPTES DE RESSOURCES DURABLES
            ['code' => '10', 'name' => 'CAPITAL', 'type' => 'equity', 'category' => 'class_1', 'parent_id' => null],
            ['code' => '101', 'name' => 'Capital social', 'type' => 'equity', 'category' => 'class_1', 'parent_code' => '10'],
            ['code' => '11', 'name' => 'RESERVES', 'type' => 'equity', 'category' => 'class_1', 'parent_id' => null],
            ['code' => '12', 'name' => 'REPORT A NOUVEAU', 'type' => 'equity', 'category' => 'class_1', 'parent_id' => null],
            ['code' => '13', 'name' => 'RESULTAT NET DE L\'EXERCICE', 'type' => 'equity', 'category' => 'class_1', 'parent_id' => null],
            ['code' => '14', 'name' => 'SUBVENTIONS D\'INVESTISSEMENT', 'type' => 'equity', 'category' => 'class_1', 'parent_id' => null],
            ['code' => '16', 'name' => 'EMPRUNTS ET DETTES ASSIMILEES', 'type' => 'liability', 'category' => 'class_1', 'parent_id' => null],

            // CLASSE 2 : COMPTES D'ACTIF IMMOBILISE
            ['code' => '20', 'name' => 'CHARGES IMMOBILISEES', 'type' => 'asset', 'category' => 'class_2', 'parent_id' => null],
            ['code' => '21', 'name' => 'IMMOBILISATIONS INCORPORELLES', 'type' => 'asset', 'category' => 'class_2', 'parent_id' => null],
            ['code' => '22', 'name' => 'TERRAINS', 'type' => 'asset', 'category' => 'class_2', 'parent_id' => null],
            ['code' => '23', 'name' => 'BATIMENTS, INSTALLATIONS TECHNIQUES ET AGENCEMENTS', 'type' => 'asset', 'category' => 'class_2', 'parent_id' => null],
            ['code' => '24', 'name' => 'MATERIEL', 'type' => 'asset', 'category' => 'class_2', 'parent_id' => null],
            ['code' => '244', 'name' => 'Matériel et mobilier', 'type' => 'asset', 'category' => 'class_2', 'parent_code' => '24'],
            ['code' => '245', 'name' => 'Matériel de transport', 'type' => 'asset', 'category' => 'class_2', 'parent_code' => '24'],
            ['code' => '28', 'name' => 'AMORTISSEMENTS', 'type' => 'asset', 'category' => 'class_2', 'parent_id' => null],

            // CLASSE 3 : COMPTES DE STOCKS
            ['code' => '31', 'name' => 'MARCHANDISES', 'type' => 'asset', 'category' => 'class_3', 'parent_id' => null],
            ['code' => '32', 'name' => 'MATIERES PREMIERES ET FOURNITURES LIEES', 'type' => 'asset', 'category' => 'class_3', 'parent_id' => null],
            ['code' => '33', 'name' => 'AUTRES APPROVISIONNEMENTS', 'type' => 'asset', 'category' => 'class_3', 'parent_id' => null],
            ['code' => '36', 'name' => 'PRODUITS FINIS', 'type' => 'asset', 'category' => 'class_3', 'parent_id' => null],

            // CLASSE 4 : COMPTES DE TIERS
            ['code' => '40', 'name' => 'FOURNISSEURS ET COMPTES RATTACHES', 'type' => 'liability', 'category' => 'class_4', 'parent_id' => null],
            ['code' => '401', 'name' => 'Fournisseurs', 'type' => 'liability', 'category' => 'class_4', 'parent_code' => '40'],
            ['code' => '4011', 'name' => 'Fournisseurs - achats de biens et services', 'type' => 'liability', 'category' => 'class_4', 'parent_code' => '401'],
            ['code' => '41', 'name' => 'CLIENTS ET COMPTES RATTACHES', 'type' => 'asset', 'category' => 'class_4', 'parent_id' => null],
            ['code' => '411', 'name' => 'Clients', 'type' => 'asset', 'category' => 'class_4', 'parent_code' => '41'],
            ['code' => '4111', 'name' => 'Clients - ventes de biens et services', 'type' => 'asset', 'category' => 'class_4', 'parent_code' => '411'],
            ['code' => '43', 'name' => 'PERSONNEL', 'type' => 'liability', 'category' => 'class_4', 'parent_id' => null],
            ['code' => '44', 'name' => 'ETAT ET COLLECTIVITES PUBLIQUES', 'type' => 'liability', 'category' => 'class_4', 'parent_id' => null],
            ['code' => '445', 'name' => 'État, TVA', 'type' => 'liability', 'category' => 'class_4', 'parent_code' => '44'],
            ['code' => '4451', 'name' => 'État, TVA collectée', 'type' => 'liability', 'category' => 'class_4', 'parent_code' => '445'],
            ['code' => '4452', 'name' => 'État, TVA déductible', 'type' => 'asset', 'category' => 'class_4', 'parent_code' => '445'],

            // CLASSE 5 : COMPTES DE TRESORERIE
            ['code' => '52', 'name' => 'BANQUES', 'type' => 'asset', 'category' => 'class_5', 'parent_id' => null],
            ['code' => '521', 'name' => 'Banques locales', 'type' => 'asset', 'category' => 'class_5', 'parent_code' => '52'],
            ['code' => '57', 'name' => 'CAISSE', 'type' => 'asset', 'category' => 'class_5', 'parent_id' => null],
            ['code' => '571', 'name' => 'Caisse siège', 'type' => 'asset', 'category' => 'class_5', 'parent_code' => '57'],

            // CLASSE 6 : COMPTES DE CHARGES
            ['code' => '60', 'name' => 'ACHATS ET VARIATIONS DE STOCKS', 'type' => 'expense', 'category' => 'class_6', 'parent_id' => null],
            ['code' => '601', 'name' => 'Achats de marchandises', 'type' => 'expense', 'category' => 'class_6', 'parent_code' => '60'],
            ['code' => '6011', 'name' => 'Achats de marchandises dans la région', 'type' => 'expense', 'category' => 'class_6', 'parent_code' => '601'],
            ['code' => '61', 'name' => 'TRANSPORTS', 'type' => 'expense', 'category' => 'class_6', 'parent_id' => null],
            ['code' => '62', 'name' => 'SERVICES EXTERIEURS A', 'type' => 'expense', 'category' => 'class_6', 'parent_id' => null],
            ['code' => '63', 'name' => 'SERVICES EXTERIEURS B', 'type' => 'expense', 'category' => 'class_6', 'parent_id' => null],
            ['code' => '64', 'name' => 'IMPOTS ET TAXES', 'type' => 'expense', 'category' => 'class_6', 'parent_id' => null],
            ['code' => '66', 'name' => 'CHARGES DE PERSONNEL', 'type' => 'expense', 'category' => 'class_6', 'parent_id' => null],

            // CLASSE 7 : COMPTES DE PRODUITS
            ['code' => '70', 'name' => 'VENTES', 'type' => 'revenue', 'category' => 'class_7', 'parent_id' => null],
            ['code' => '701', 'name' => 'Ventes de marchandises', 'type' => 'revenue', 'category' => 'class_7', 'parent_code' => '70'],
            ['code' => '7011', 'name' => 'Ventes de marchandises dans la région', 'type' => 'revenue', 'category' => 'class_7', 'parent_code' => '701'],
            ['code' => '706', 'name' => 'Services vendus', 'type' => 'revenue', 'category' => 'class_7', 'parent_code' => '70'],
            ['code' => '71', 'name' => 'SUBVENTIONS D\'EXPLOITATION', 'type' => 'revenue', 'category' => 'class_7', 'parent_id' => null],
            ['code' => '75', 'name' => 'AUTRES PRODUITS', 'type' => 'revenue', 'category' => 'class_7', 'parent_id' => null],
        ];

        $createdAccounts = [];

        foreach ($accounts as $accountData) {
            $parentId = null;

            if (isset($accountData['parent_code'])) {
                $parentId = $createdAccounts[$accountData['parent_code']] ?? null;
            } elseif (isset($accountData['parent_id'])) {
                $parentId = $accountData['parent_id'];
            }

            $account = Account::create([
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'category' => $accountData['category'],
                'parent_id' => $parentId,
                'is_sub_account' => $parentId !== null,
                'is_system' => true,
            ]);

            $createdAccounts[$accountData['code']] = $account->id;
        }
    }
}
