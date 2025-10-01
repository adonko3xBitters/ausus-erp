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

                                Forms\Components\Placeholder::make('variants_info')
                                    ->label('')
                                    ->content('Les variantes seront gérées après la création du produit.')
                                    ->visible(fn (callable $get) => $get('has_variants'))
                                    ->columnSpanFull(),
                            ]),

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
