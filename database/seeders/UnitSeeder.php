<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Pièce', 'short_name' => 'pce', 'description' => 'Unité de comptage'],
            ['name' => 'Kilogramme', 'short_name' => 'kg', 'description' => 'Unité de poids'],
            ['name' => 'Gramme', 'short_name' => 'g', 'description' => 'Unité de poids'],
            ['name' => 'Tonne', 'short_name' => 't', 'description' => 'Unité de poids'],
            ['name' => 'Mètre', 'short_name' => 'm', 'description' => 'Unité de longueur'],
            ['name' => 'Centimètre', 'short_name' => 'cm', 'description' => 'Unité de longueur'],
            ['name' => 'Mètre carré', 'short_name' => 'm²', 'description' => 'Unité de surface'],
            ['name' => 'Mètre cube', 'short_name' => 'm³', 'description' => 'Unité de volume'],
            ['name' => 'Litre', 'short_name' => 'l', 'description' => 'Unité de volume'],
            ['name' => 'Heure', 'short_name' => 'h', 'description' => 'Unité de temps'],
            ['name' => 'Jour', 'short_name' => 'j', 'description' => 'Unité de temps'],
            ['name' => 'Carton', 'short_name' => 'ctn', 'description' => 'Emballage'],
            ['name' => 'Palette', 'short_name' => 'plt', 'description' => 'Emballage'],
            ['name' => 'Sac', 'short_name' => 'sac', 'description' => 'Emballage'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}
