<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /**
     * Recalculer les totaux après création
     */
    protected function afterCreate(): void
    {
        // Calculer les totaux après création
        $this->record->calculateTotals();
    }
}
