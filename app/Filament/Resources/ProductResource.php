<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationGroup = 'Stocks';

    protected static ?string $navigationLabel = 'Produits';

    protected static ?string $modelLabel = 'Produit';

    protected static ?string $pluralModelLabel = 'Produits';

    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informations générales')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('Référence (SKU)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->default(fn () => 'PRD-' . strtoupper(\Illuminate\Support\Str::random(8)))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state)))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(1),

                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options([
                                        'product' => 'Produit',
                                        'service' => 'Service',
                                    ])
                                    ->default('product')
                                    ->required()
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('category_id')
                                    ->label('Catégorie')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nom')
                                            ->required(),
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\Select::make('brand_id')
                                    ->label('Marque')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nom')
                                            ->required(),
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\Select::make('unit_id')
                                    ->label('Unité de mesure')
                                    ->relationship('unit', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->short_name})")
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('images')
                                    ->label('Images')
                                    ->image()
                                    ->multiple()
                                    ->directory('products')
                                    ->maxSize(2048)
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),

                        Forms\Components\Tabs\Tab::make('Prix et coûts')
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label('Prix d\'achat')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('FCFA')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('sale_price')
                                    ->label('Prix de vente')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('FCFA')
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Coût de revient (CMP)')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('FCFA')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Calculé automatiquement')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('sale_tax_id')
                                    ->label('Taxe à la vente')
                                    ->relationship('saleTax', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('purchase_tax_id')
                                    ->label('Taxe à l\'achat')
                                    ->relationship('purchaseTax', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),
                            ])
                            ->columns(3),

                        Forms\Components\Tabs\Tab::make('Comptabilité')
                            ->schema([
                                Forms\Components\Select::make('sales_account_id')
                                    ->label('Compte de vente')
                                    ->relationship('salesAccount', 'name', fn ($query) => $query->where('code', 'like', '70%'))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('purchase_account_id')
                                    ->label('Compte d\'achat')
                                    ->relationship('purchaseAccount', 'name', fn ($query) => $query->where('code', 'like', '60%'))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('inventory_account_id')
                                    ->label('Compte de stock')
                                    ->relationship('inventoryAccount', 'name', fn ($query) => $query->where('code', 'like', '3%'))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),
                            ])
                            ->columns(3),

                        Forms\Components\Tabs\Tab::make('Stock')
                            ->schema([
                                Forms\Components\Toggle::make('track_inventory')
                                    ->label('Suivre le stock')
                                    ->default(true)
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('cost_method')
                                    ->label('Méthode de coût')
                                    ->options([
                                        'average' => 'Coût Moyen Pondéré (CMP)',
                                        'fifo' => 'FIFO (Premier Entré, Premier Sorti)',
                                        'lifo' => 'LIFO (Dernier Entré, Premier Sorti)',
                                    ])
                                    ->default('average')
                                    ->required()
                                    ->visible(fn (callable $get) => $get('track_inventory'))
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('alert_quantity')
                                    ->label('Seuil d\'alerte')
                                    ->numeric()
                                    ->default(10)
                                    ->minValue(0)
                                    ->helperText('Alerte lorsque le stock descend sous cette quantité')
                                    ->visible(fn (callable $get) => $get('track_inventory'))
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->hidden(fn (callable $get) => $get('type') === 'service'),

                        Forms\Components\Tabs\Tab::make('Variantes')
                            ->schema([
                                Forms\Components\Toggle::make('has_variants')
                                    ->label('Ce produit a des variantes')
                                    ->helperText('Activez cette option si le produit existe en plusieurs versions (tailles, couleurs...)')
                                    ->reactive()
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Générer automatiquement les variantes')
                                    ->description('Sélectionnez les attributs et cliquez sur le bouton ci-dessous pour générer toutes les combinaisons possibles')
                                    ->schema([
                                        Forms\Components\Select::make('variant_attributes')
                                            ->label('Attributs à utiliser')
                                            ->multiple()
                                            ->options(function () {
                                                return \App\Models\ProductAttribute::where('is_active', true)
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->helperText('Les variantes seront créées automatiquement avec toutes les combinaisons possibles')
                                            ->reactive()
                                            ->dehydrated(false) // Ne pas sauvegarder dans la BD
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                if (!empty($state)) {
                                                    $attributes = \App\Models\ProductAttribute::whereIn('id', $state)->get();
                                                    $count = 1;
                                                    foreach ($attributes as $attr) {
                                                        $count *= count($attr->values);
                                                    }
                                                    $set('variants_preview', "Cela générera {$count} variante(s)");
                                                } else {
                                                    $set('variants_preview', null);
                                                }
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('variants_preview')
                                            ->hiddenLabel(true)
                                            ->content(fn ($get) => $get('variants_preview'))
                                            ->visible(fn ($get) => $get('variants_preview') !== null)
                                            ->columnSpan(1),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('generate_variants')
                                                ->label('Générer les variantes')
                                                ->icon('heroicon-o-sparkles')
                                                ->color('success')
                                                ->requiresConfirmation()
                                                ->modalHeading('Générer les variantes')
                                                ->modalDescription(fn (callable $get) => $get('variants_preview') ?? 'Sélectionnez d\'abord des attributs')
                                                ->disabled(fn (callable $get) => empty($get('variant_attributes')))
                                                ->action(function (callable $get, callable $set, $record) {
                                                    if (!$record) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Veuillez d\'abord sauvegarder le produit')
                                                            ->warning()
                                                            ->send();
                                                        return;
                                                    }

                                                    $attributeIds = $get('variant_attributes');
                                                    if (empty($attributeIds)) {
                                                        return;
                                                    }

                                                    $created = static::generateProductVariants($record, $attributeIds);

                                                    \Filament\Notifications\Notification::make()
                                                        ->title("{$created} variante(s) créée(s)")
                                                        ->success()
                                                        ->send();

                                                    // Recharger les variantes
                                                    $set('variant_attributes', []);
                                                }),
                                        ])
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->visible(fn (callable $get, $record) => $get('has_variants') && $record),

                                Forms\Components\Section::make('Variantes du produit')
                                    ->description('Gérez manuellement les variantes ou modifiez celles générées automatiquement')
                                    ->schema([
                                        Forms\Components\Repeater::make('variants')
                                            ->relationship('variants')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('sku')
                                                            ->label('SKU')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->default(fn ($get) =>
                                                            \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8))
                                                            )
                                                            ->columnSpan(1),

                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Nom de la variante')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->columnSpan(2),
                                                    ]),

                                                Forms\Components\KeyValue::make('attributes')
                                                    ->label('Attributs')
                                                    ->keyLabel('Nom de l\'attribut')
                                                    ->valueLabel('Valeur')
                                                    ->addActionLabel('Ajouter un attribut')
                                                    ->reorderable(false)
                                                    ->columnSpanFull(),

                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('additional_price')
                                                            ->label('Prix supplémentaire')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->prefix('+/-')
                                                            ->suffix('FCFA')
                                                            ->helperText('Ajouté au prix du produit parent')
                                                            ->columnSpan(1),

                                                        Forms\Components\Placeholder::make('calculated_price')
                                                            ->label('Prix final')
                                                            ->content(function (callable $get, $record) {
                                                                if (!$record || !$record->product) {
                                                                    return 'N/A';
                                                                }
                                                                $basePrice = $record->product->sale_price;
                                                                $additional = $get('additional_price') ?? 0;
                                                                return number_format($basePrice + $additional, 0) . ' FCFA';
                                                            })
                                                            ->columnSpan(1),

                                                        Forms\Components\Toggle::make('is_active')
                                                            ->label('Active')
                                                            ->default(true)
                                                            ->inline(false)
                                                            ->columnSpan(1),
                                                    ]),

                                                Forms\Components\FileUpload::make('image')
                                                    ->label('Image spécifique')
                                                    ->image()
                                                    ->directory('product-variants')
                                                    ->maxSize(2048)
                                                    ->columnSpanFull(),
                                            ])
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Nouvelle variante')
                                            ->collapsed()
                                            ->collapsible()
                                            ->cloneable()
                                            ->reorderable(false)
                                            ->addActionLabel('Ajouter une variante manuellement')
                                            ->defaultItems(0)
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn (callable $get, $record) => $get('has_variants') && $record)
                                    ->collapsible(),
                            ])
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\Tabs\Tab::make('Options')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Actif')
                                    ->default(true)
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Produit vedette')
                                    ->default(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Image')
                    ->getStateUsing(fn ($record) => $record->images[0] ?? null)
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Product&color=7F9CF5&background=EBF4FF')
                    ->circular(),

                Tables\Columns\TextColumn::make('sku')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->category?->name),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state === 'product' ? 'Produit' : 'Service')
                    ->badge()
                    ->color(fn ($state) => $state === 'product' ? 'success' : 'info'),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Prix de vente')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock')
                    ->getStateUsing(fn ($record) => $record->track_inventory ? $record->total_stock : 'N/A')
                    ->badge()
                    ->color(fn ($record) => $record->track_inventory && $record->isLowStock() ? 'danger' : 'success')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'product' => 'Produit',
                        'service' => 'Service',
                    ]),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Marque')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('track_inventory')
                    ->label('Suivi du stock'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock bas')
                    ->query(fn ($query) => $query->whereHas('stocks', function ($q) {
                        $q->havingRaw('SUM(available_quantity) <= alert_quantity');
                    })),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function generateProductVariants($product, array $attributeIds): int
    {
        $attributes = \App\Models\ProductAttribute::whereIn('id', $attributeIds)->get();

        if ($attributes->isEmpty()) {
            return 0;
        }

        // Générer toutes les combinaisons
        $combinations = static::generateCombinations($attributes);

        $created = 0;

        foreach ($combinations as $combination) {
            // Vérifier si existe déjà
            $exists = \App\Models\ProductVariant::where('product_id', $product->id)
                ->whereJsonContains('attributes', $combination)
                ->exists();

            if ($exists) {
                continue;
            }

            // Créer le nom
            $variantName = $product->name . ' - ' . implode(' ', array_values($combination));

            // SKU unique
            $variantSku = $product->sku . '-' . strtoupper(\Illuminate\Support\Str::random(4));

            // Créer la variante
            \App\Models\ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variantSku,
                'name' => $variantName,
                'attributes' => $combination,
                'additional_price' => 0,
                'is_active' => true,
            ]);

            $created++;
        }

        return $created;
    }

    protected static function generateCombinations(iterable $attributes): array
    {
        $result = [[]];

        foreach ($attributes as $attribute) {
            $temp = [];
            foreach ($result as $combination) {
                foreach ($attribute->values as $value) {
                    $newCombination = $combination;
                    $newCombination[$attribute->name] = $value;
                    $temp[] = $newCombination;
                }
            }
            $result = $temp;
        }

        return $result;
    }

    /*public static function getNavigationBadge(): ?string
    {
        $lowStock = static::getModel()::where('track_inventory', true)
            ->whereHas('stocks', function ($q) {
                $q->havingRaw('SUM(available_quantity) <= alert_quantity');
            })
            ->count();

        return $lowStock > 0 ? (string) $lowStock : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }*/
}
