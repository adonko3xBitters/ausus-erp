<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Payment;

class RecentPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Derniers Mouvements de Trésorerie';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->orderBy('payment_date', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('payment_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'inflow',
                        'danger' => 'outflow',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'inflow' => 'Encaissement',
                        'outflow' => 'Décaissement',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Compte')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money(currency()->code)
                    ->color(fn (Payment $record): string =>
                    $record->payment_type === 'inflow' ? 'success' : 'danger'
                    )
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Espèces',
                        'bank_transfer' => 'Virement',
                        'check' => 'Chèque',
                        'mobile_money' => 'Mobile Money',
                        default => $state,
                    }),
            ]);
    }
}
