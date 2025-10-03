<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Product;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Alertes Stock - Produits à Réapprovisionner';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with('stocks')
                    ->withSum('stocks', 'available_quantity')
                    ->whereHas('stocks', function ($query) {
                        $query->whereColumn('available_quantity', '<=', 'products.alert_quantity');
                    })
                    ->where('products.track_inventory', true)
                    ->orderBy('stocks_sum_available_quantity', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('Référence')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stocks_sum_available_quantity')
                    ->label('Stock Actuel')
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('alert_quantity')
                    ->label('Seuil Alerte')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Prix Unitaire')
                    ->money(currency()->code)
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->badge(),
            ]);
    }
}
