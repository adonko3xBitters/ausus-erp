<?php

namespace App\Filament\Pages;

use App\Models\Stock;
use App\Models\Warehouse;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;

class StockStatus extends Page implements HasTable
{
    use HasPageShield;

    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationGroup = 'Stocks';

    protected static ?string $navigationLabel = 'État des stocks';

    protected static string $view = 'filament.pages.stock-status';

    protected static ?int $navigationSort = 7;

    public ?int $warehouseId = null;

    public function mount(): void
    {
        $this->warehouseId = Warehouse::getDefault()?->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // TODO: Implémenter l'export Excel
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->with(['product', 'productVariant', 'warehouse'])
                    ->when($this->warehouseId, fn ($query) => $query->where('warehouse_id', $this->warehouseId))
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->product->category?->name),

                Tables\Columns\TextColumn::make('productVariant.name')
                    ->label('Variante')
                    ->badge()
                    ->color('primary')
                    ->default('-'),

                Tables\Columns\TextColumn::make('warehouse.code')
                    ->label('Entrepôt')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label('Réservé')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Disponible')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->color(fn ($state, $record) =>
                    $state <= $record->product->alert_quantity ? 'danger' : 'success'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.alert_quantity')
                    ->label('Seuil alerte')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Valeur stock')
                    ->getStateUsing(fn ($record) => $record->quantity * $record->product->cost_price)
                    ->money('XOF')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Entrepôt')
                    ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => Warehouse::getDefault()?->id),

                Tables\Filters\SelectFilter::make('product.category_id')
                    ->label('Catégorie')
                    ->relationship('product.category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('product.brand_id')
                    ->label('Marque')
                    ->relationship('product.brand', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock bas')
                    ->query(fn ($query) => $query->whereColumn('available_quantity', '<=', 'alert_quantity')),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Rupture de stock')
                    ->query(fn ($query) => $query->where('available_quantity', '<=', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust')
                    ->label('Ajuster')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('new_quantity')
                            ->label('Nouvelle quantité')
                            ->numeric()
                            ->required()
                            ->default(fn ($record) => $record->quantity),

                        Forms\Components\Textarea::make('notes')
                            ->label('Motif de l\'ajustement')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Stock $record, array $data) {
                        $stockService = app(\App\Services\StockService::class);

                        $stockService->adjustStock([
                            'warehouse_id' => $record->warehouse_id,
                            'product_id' => $record->product_id,
                            'product_variant_id' => $record->product_variant_id,
                            'new_quantity' => $data['new_quantity'],
                            'movement_date' => now()->format('Y-m-d'),
                            'notes' => $data['notes'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Stock ajusté')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('movements')
                    ->label('Historique')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.stock-movements.index', [
                        'tableFilters' => [
                            'product_id' => ['value' => $record->product_id],
                            'warehouse_id' => ['value' => $record->warehouse_id],
                        ],
                    ])),
            ])
            ->bulkActions([
                // Pas d'actions en masse
            ])
            ->defaultSort('available_quantity', 'asc')
            ->poll('30s');
    }
}
