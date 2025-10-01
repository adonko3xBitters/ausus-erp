<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Services\StockService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Stocks';

    protected static ?string $navigationLabel = 'Mouvements de stock';

    protected static ?string $modelLabel = 'Mouvement';

    protected static ?string $pluralModelLabel = 'Mouvements de stock';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du mouvement')
                    ->schema([
                        Forms\Components\TextInput::make('movement_number')
                            ->label('N° Mouvement')
                            ->default(fn () => StockMovement::generateNumber())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->label('Type de mouvement')
                            ->options([
                                'in' => 'Entrée de stock',
                                'out' => 'Sortie de stock',
                                'adjustment' => 'Ajustement',
                                'transfer' => 'Transfert entre entrepôts',
                            ])
                            ->required()
                            ->reactive()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('movement_date')
                            ->label('Date du mouvement')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Produit')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produit')
                            ->relationship('product', 'name', fn ($query) => $query->where('track_inventory', true))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->sku} - {$record->name}")
                            ->searchable(['sku', 'name'])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = \App\Models\Product::find($state);
                                    $set('cost_per_unit', $product->cost_price);

                                    // Charger les variantes si le produit en a
                                    if ($product->has_variants) {
                                        $set('has_variants', true);
                                    } else {
                                        $set('has_variants', false);
                                        $set('product_variant_id', null);
                                    }
                                }
                            })
                            ->columnSpan(2),

                        Forms\Components\Select::make('product_variant_id')
                            ->label('Variante')
                            ->options(function (callable $get) {
                                $productId = $get('product_id');
                                if (!$productId) {
                                    return [];
                                }

                                return \App\Models\ProductVariant::where('product_id', $productId)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->visible(fn (callable $get) => $get('has_variants') ?? false)
                            ->columnSpan(1),

                        Forms\Components\Hidden::make('has_variants')
                            ->default(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Entrepôt')
                    ->schema([
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Entrepôt')
                            ->relationship('warehouse', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => \App\Models\Warehouse::getDefault()?->id)
                            ->visible(fn (callable $get) => $get('type') !== 'transfer')
                            ->columnSpan(1),

                        Forms\Components\Select::make('from_warehouse_id')
                            ->label('Entrepôt source')
                            ->relationship('fromWarehouse', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('type') === 'transfer')
                            ->columnSpan(1),

                        Forms\Components\Select::make('to_warehouse_id')
                            ->label('Entrepôt destination')
                            ->relationship('toWarehouse', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('type') === 'transfer')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Quantité et coût')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $costPerUnit = $get('cost_per_unit') ?? 0;
                                $set('total_cost', $state * $costPerUnit);
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('cost_per_unit')
                            ->label('Coût unitaire')
                            ->numeric()
                            ->default(0)
                            ->suffix('FCFA')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $quantity = $get('quantity') ?? 0;
                                $set('total_cost', $state * $quantity);
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total_cost')
                            ->label('Coût total')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('FCFA')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Informations complémentaires')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->maxLength(255)
                            ->placeholder('N° document source...')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('movement_number')
                    ->label('N° Mouvement')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('movement_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'in' => 'Entrée',
                        'out' => 'Sortie',
                        'adjustment' => 'Ajustement',
                        'transfer' => 'Transfert',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        'transfer' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->description(fn ($record) => $record->product->sku),

                Tables\Columns\TextColumn::make('productVariant.name')
                    ->label('Variante')
                    ->badge()
                    ->color('primary')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('warehouse.code')
                    ->label('Entrepôt')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->type === 'transfer') {
                            return "{$record->fromWarehouse->code} → {$record->toWarehouse->code}";
                        }
                        return $record->warehouse->code;
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(decimalPlaces: 2)
                    ->alignEnd()
                    ->color(fn ($record) => match($record->type) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Coût total')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'in' => 'Entrée',
                        'out' => 'Sortie',
                        'adjustment' => 'Ajustement',
                        'transfer' => 'Transfert',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Entrepôt')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('movement_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('movement_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('La suppression de ce mouvement restaurera les quantités en stock. Êtes-vous sûr ?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('movement_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }
}
