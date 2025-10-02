<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    /**
     * Recalculer les totaux après création
     */
    protected function afterCreate(): void
    {
        // Calculer les totaux après création
        $this->record->calculateTotals();
    }
}
