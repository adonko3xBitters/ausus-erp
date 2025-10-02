<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Bill;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['paymentable_type'] = null;
        $data['paymentable_id'] = null;

        if (isset($data['invoice_id'])) {
            $data['paymentable_type'] = Invoice::class;
            $data['paymentable_id'] = $data['invoice_id'];
        }

        if (isset($data['bill_id'])) {
            $data['paymentable_type'] = Bill::class;
            $data['paymentable_id'] = $data['bill_id'];
        }

        return $data;
    }
}
