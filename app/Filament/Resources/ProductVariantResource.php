<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationGroup = 'Stocks';

    protected static ?string $navigationLabel = 'Variantes de produits';

    protected static ?string $modelLabel = 'Variante';

    protected static ?string $pluralModelLabel = 'Variantes de produits';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de base')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Produit parent')
                            ->relationship('product', 'name', fn ($query) => $query->where('has_variants', true))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->sku} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record !== null)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la variante')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Attributs')
                    ->schema([
                        Forms\Components\KeyValue::make('attributes')
                            ->label('Attributs de la variante')
                            ->keyLabel('Attribut')
                            ->valueLabel('Valeur')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Prix et image')
                    ->schema([
                        Forms\Components\TextInput::make('additional_price')
                            ->label('Prix supplémentaire')
                            ->numeric()
                            ->default(0)
                            ->prefix('+/-')
                            ->suffix('FCFA')
                            ->helperText('Montant à ajouter ou soustraire au prix du produit parent')
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('final_price')
                            ->label('Prix de vente final')
                            ->content(function (callable $get, $record) {
                                if (!$record) {
                                    return 'Sauvegardez d\'abord pour voir le prix final';
                                }
                                return number_format($record->sale_price, 0) . ' FCFA';
                            })
                            ->columnSpan(1),

                        Forms\Components\FileUpload::make('image')
                            ->label('Image de la variante')
                            ->image()
                            ->directory('product-variants')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stock')
                    ->schema([
                        Forms\Components\Placeholder::make('total_stock')
                            ->label('Stock total')
                            ->content(function ($record) {
                                if (!$record) {
                                    return '-';
                                }
                                return number_format($record->total_stock, 2);
                            }),

                        Forms\Components\Placeholder::make('available_stock')
                            ->label('Stock disponible')
                            ->content(function ($record) {
                                if (!$record) {
                                    return '-';
                                }
                                return number_format($record->available_stock, 2);
                            }),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record !== null),

                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->defaultImageUrl(fn ($record) => $record->product->featured_image ?? 'https://ui-avatars.com/api/?name=Variant')
                    ->circular(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit parent')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attributes')
                    ->label('Attributs')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return '-';
                        }
                        return collect($state)
                            ->map(fn ($value, $key) => "{$key}: {$value}")
                            ->implode(', ');
                    })
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Prix de vente')
                    ->money('XOF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock')
                    ->getStateUsing(fn ($record) => $record->total_stock)
                    ->badge()
                    ->color(fn ($record) => $record->total_stock > 0 ? 'success' : 'danger'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('adjust_stock')
                        ->label('Ajuster le stock')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('warehouse_id')
                                ->label('Entrepôt')
                                ->relationship('product.warehouses', 'name')
                                ->default(fn () => \App\Models\Warehouse::getDefault()?->id)
                                ->required(),

                            Forms\Components\TextInput::make('new_quantity')
                                ->label('Nouvelle quantité')
                                ->numeric()
                                ->required(),

                            Forms\Components\Textarea::make('notes')
                                ->label('Motif')
                                ->required(),
                        ])
                        ->action(function (array $data, $record) {
                            $stockService = app(\App\Services\StockService::class);

                            $stockService->adjustStock([
                                'warehouse_id' => $data['warehouse_id'],
                                'product_id' => $record->product_id,
                                'product_variant_id' => $record->id,
                                'new_quantity' => $data['new_quantity'],
                                'movement_date' => now()->format('Y-m-d'),
                                'notes' => $data['notes'],
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Stock ajusté')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Dupliquer')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newVariant = $record->replicate();
                            $newVariant->sku = $record->product->sku . '-' . strtoupper(\Illuminate\Support\Str::random(4));
                            $newVariant->name = $record->name . ' (Copie)';
                            $newVariant->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Variante dupliquée')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
