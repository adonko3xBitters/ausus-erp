<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Factures clients';

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $pluralModelLabel = 'Factures clients';

    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('N° Facture')
                            ->default(fn () => Invoice::generateNumber())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->customer_number} - {$record->full_name}")
                            ->searchable(['customer_number', 'name', 'company_name'])
                            ->required()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $customer = \App\Models\Customer::find($state);
                                    $set('currency_id', $customer->currency_id);
                                    $set('payment_terms', $customer->payment_terms);
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('invoice_date')
                            ->label('Date facture')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $paymentTerms = $get('payment_terms') ?? 30;
                                if ($state) {
                                    $set('due_date', now()->parse($state)->addDays($paymentTerms));
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Date d\'échéance')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->maxLength(255)
                            ->placeholder('N° commande client...')
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'sent' => 'Envoyée',
                                'viewed' => 'Vue',
                                'partial' => 'Partiellement payée',
                                'paid' => 'Payée',
                                'overdue' => 'En retard',
                                'cancelled' => 'Annulée',
                            ])
                            ->default('draft')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('currency_id')
                            ->label('Devise')
                            ->relationship('currency', 'code')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\Hidden::make('payment_terms')
                            ->default(30),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Lignes de facture')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                /*Forms\Components\Select::make('item_type')
                                    ->label('Type')
                                    ->options([
                                        'product' => 'Produit',
                                        'service' => 'Service',
                                    ])
                                    ->default('product')
                                    ->required()
                                    ->columnSpan(1),*/
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->relationship('product', 'name', fn ($query) => $query->where('is_active', true))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->sku} - {$record->name}")
                                    ->searchable(['sku', 'name'])
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);

                                            // Si le produit n'a pas de variantes
                                            if (!$product->has_variants) {
                                                $set('description', $product->name);
                                                $set('unit_price', $product->sale_price);
                                                $set('tax_id', $product->sale_tax_id);
                                                $set('unit', $product->unit->short_name);
                                                $set('has_variants', false);
                                                $set('product_variant_id', null);
                                            } else {
                                                // Si le produit a des variantes
                                                $set('has_variants', true);
                                                $set('description', $product->name);
                                                $set('unit', $product->unit->short_name);
                                                // Ne pas mettre de prix, attendre la sélection de la variante
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

                                        $product = \App\Models\Product::find($productId);
                                        if (!$product || !$product->has_variants) {
                                            return [];
                                        }

                                        return $product->variants()
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($variant) {
                                                $attributes = collect($variant->attributes)
                                                    ->map(fn($value, $key) => "$value")
                                                    ->implode(', ');
                                                return [$variant->id => "{$variant->name} ({$attributes}) - " . number_format($variant->sale_price, 0) . " FCFA"];
                                            })
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $variant = \App\Models\ProductVariant::find($state);
                                            $set('unit_price', $variant->sale_price);
                                            $set('description', $variant->name);
                                        }
                                    })
                                    ->visible(fn (callable $get) => $get('has_variants') ?? false)
                                    ->required(fn (callable $get) => $get('has_variants') ?? false)
                                    ->columnSpan(2),

                                Forms\Components\Hidden::make('has_variants')->default(false),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Prix unitaire')
                                    ->numeric()
                                    ->required()
                                    ->suffix('FCFA')
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('discount_percent')
                                    ->label('Remise (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('tax_id')
                                    ->label('Taxe')
                                    ->relationship('tax', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\Placeholder::make('line_total')
                                    ->label('Total')
                                    ->content(function ($get) {
                                        $quantity = $get('quantity') ?? 0;
                                        $unitPrice = $get('unit_price') ?? 0;
                                        $discountPercent = $get('discount_percent') ?? 0;

                                        $subtotal = $quantity * $unitPrice;
                                        $discount = $subtotal * ($discountPercent / 100);
                                        $amount = $subtotal - $discount;

                                        $taxId = $get('tax_id');
                                        if ($taxId) {
                                            $tax = \App\Models\Tax::find($taxId);
                                            if ($tax) {
                                                $taxAmount = $tax->calculate($amount);
                                                $amount += $taxAmount;
                                            }
                                        }

                                        return number_format($amount, 0, ',', ' ') . ' FCFA';
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Ajouter une ligne')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Recalculer les totaux
                                $subtotal = 0;
                                $taxAmount = 0;

                                foreach ($state ?? [] as $item) {
                                    $quantity = $item['quantity'] ?? 0;
                                    $unitPrice = $item['unit_price'] ?? 0;
                                    $discountPercent = $item['discount_percent'] ?? 0;

                                    $itemSubtotal = $quantity * $unitPrice;
                                    $discount = $itemSubtotal * ($discountPercent / 100);
                                    $itemAmount = $itemSubtotal - $discount;

                                    $subtotal += $itemAmount;

                                    if (!empty($item['tax_id'])) {
                                        $tax = \App\Models\Tax::find($item['tax_id']);
                                        if ($tax) {
                                            $taxAmount += $tax->calculate($itemAmount);
                                        }
                                    }
                                }

                                $set('subtotal', $subtotal);
                                $set('tax_amount', $taxAmount);
                                $set('total', $subtotal + $taxAmount);
                                $set('amount_due', $subtotal + $taxAmount);
                            }),
                    ]),

                Forms\Components\Section::make('Totaux')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Sous-total HT')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('FCFA')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Remise globale')
                            ->numeric()
                            ->default(0)
                            ->suffix('FCFA')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $subtotal = $get('subtotal') ?? 0;
                                $taxAmount = $get('tax_amount') ?? 0;
                                $discount = $state ?? 0;
                                $set('total', $subtotal + $taxAmount - $discount);
                                $set('amount_due', $subtotal + $taxAmount - $discount);
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('TVA')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('FCFA')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total')
                            ->label('Total TTC')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('FCFA')
                            ->extraAttributes(['class' => 'font-bold text-lg'])
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Conditions et notes')
                    ->schema([
                        Forms\Components\Textarea::make('terms')
                            ->label('Conditions de paiement')
                            ->rows(2)
                            ->placeholder('Paiement à 30 jours...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('footer')
                            ->label('Pied de page')
                            ->rows(2)
                            ->placeholder('Merci pour votre confiance...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('N° Facture')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->customer->customer_number),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : null),

                Tables\Columns\TextColumn::make('total')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Payé')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount_due')
                    ->label('Reste à payer')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyée',
                        'viewed' => 'Vue',
                        'partial' => 'Partielle',
                        'paid' => 'Payée',
                        'overdue' => 'En retard',
                        'cancelled' => 'Annulée',
                        default => $state,
                    })
                    ->color(fn ($record) => $record->status_color),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Client')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyée',
                        'partial' => 'Partielle',
                        'paid' => 'Payée',
                        'overdue' => 'En retard',
                        'cancelled' => 'Annulée',
                    ])
                    ->multiple(),

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
                            ->when($data['from'], fn ($q) => $q->whereDate('invoice_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('invoice_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => in_array($record->status, ['paid', 'cancelled'])),

                Tables\Actions\Action::make('send')
                    ->label('Envoyer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        $record->markAsSent();

                        Notification::make()
                            ->title('Facture envoyée')
                            ->success()
                            ->send();
                    })
                    ->hidden(fn ($record) => $record->status !== 'draft'),

                Tables\Actions\Action::make('record_payment')
                    ->label('Enregistrer paiement')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->url(fn ($record) => route('filament.admin.resources.payments.create', ['invoice_id' => $record->id]))
                    ->hidden(fn ($record) => in_array($record->status, ['draft', 'paid', 'cancelled'])),

                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->status !== 'draft'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('invoice_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['sent', 'partial', 'overdue'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'overdue')->exists() ? 'danger' : 'success';
    }
}
