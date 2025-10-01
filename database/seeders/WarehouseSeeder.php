<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::create([
            'name' => 'Entrepôt principal',
            'code' => 'EP-01',
            'address' => 'Zone industrielle',
            'city' => 'Abidjan',
            'country_id' => 1, // Côte d'Ivoire
            'is_active' => true,
            'is_default' => true,
        ]);

        Warehouse::create([
            'name' => 'Entrepôt secondaire',
            'code' => 'ES-01',
            'address' => 'Zone portuaire',
            'city' => 'Abidjan',
            'country_id' => 1,
            'is_active' => true,
            'is_default' => false,
        ]);
    }
}
