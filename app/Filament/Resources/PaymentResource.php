<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Bill;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'Paiement';

    protected static ?string $pluralModelLabel = 'Paiements';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du paiement')
                    ->schema([
                        Forms\Components\TextInput::make('payment_number')
                            ->label('N° Paiement')
                            ->default(fn () => Payment::generateNumber())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('payment_type')
                            ->label('Type de paiement')
                            ->options([
                                'invoice' => 'Paiement client',
                                'bill' => 'Paiement fournisseur',
                            ])
                            ->required()
                            ->reactive()
                            ->columnSpan(1)
                            ->default(fn () => request()->get('invoice_id') ? 'invoice' : (request()->get('bill_id') ? 'bill' : null)),

                        Forms\Components\Select::make('invoice_id')
                            ->label('Facture client')
                            ->options(function () {
                                return Invoice::whereIn('status', ['sent', 'partial', 'overdue'])
                                    ->get()
                                    ->mapWithKeys(function ($invoice) {
                                        return [$invoice->id => "{$invoice->invoice_number} - {$invoice->customer->name} ({$invoice->amount_due} ". currency()->symbol .")"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $invoice = Invoice::find($state);
                                    $set('amount', $invoice->amount_due);
                                    $set('currency_id', $invoice->currency_id);
                                    $set('paymentable_type', Invoice::class);
                                    $set('paymentable_id', $state);
                                }
                            })
                            ->visible(fn (callable $get) => $get('payment_type') === 'invoice')
                            ->default(fn () => request()->get('invoice_id'))
                            ->columnSpan(2),

                        Forms\Components\Select::make('bill_id')
                            ->label('Facture fournisseur')
                            ->options(function () {
                                return Bill::whereIn('status', ['received', 'partial', 'overdue'])
                                    ->get()
                                    ->mapWithKeys(function ($bill) {
                                        return [$bill->id => "{$bill->bill_number} - {$bill->vendor->name} ({$bill->amount_due} ". currency()->symbol .")"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $bill = Bill::find($state);
                                    $set('amount', $bill->amount_due);
                                    $set('currency_id', $bill->currency_id);
                                    $set('paymentable_type', Bill::class);
                                    $set('paymentable_id', $state);
                                }
                            })
                            ->visible(fn (callable $get) => $get('payment_type') === 'bill')
                            ->default(fn () => request()->get('bill_id'))
                            ->columnSpan(2),

                        Forms\Components\Hidden::make('paymentable_type'),
                        Forms\Components\Hidden::make('paymentable_id'),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date de paiement')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('amount')
                            ->label('Montant')
                            ->numeric()
                            ->required()
                            ->suffix(currency()->symbol)
                            ->columnSpan(1),

                        Forms\Components\Select::make('payment_method_id')
                            ->label('Mode de paiement')
                            ->relationship('paymentMethod', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $method = \App\Models\PaymentMethod::find($state);
                                    if ($method->default_account_id) {
                                        $set('account_id', $method->default_account_id);
                                    }
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\Select::make('account_id')
                            ->label('Compte')
                            ->relationship('account', 'name', fn ($query) => $query->whereIn('code', ['521', '571']))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Compte banque ou caisse')
                            ->columnSpan(1),

                        Forms\Components\Select::make('currency_id')
                            ->label('Devise')
                            ->relationship('currency', 'code')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->maxLength(255)
                            ->placeholder('N° transaction, chèque...')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('N° Paiement')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paymentable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state === Invoice::class ? 'Client' : 'Fournisseur')
                    ->badge()
                    ->color(fn ($state) => $state === Invoice::class ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('paymentable')
                    ->label('Document')
                    ->getStateUsing(function ($record) {
                        if ($record->paymentable instanceof Invoice) {
                            return "{$record->paymentable->invoice_number} - {$record->paymentable->customer->name}";
                        }
                        return "{$record->paymentable->bill_number} - {$record->paymentable->vendor->name}";
                    })
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money(currency()->code)
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Mode de paiement')
                    ->badge(),

                Tables\Columns\TextColumn::make('account.code')
                    ->label('Compte')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('paymentable_type')
                    ->label('Type')
                    ->options([
                        Invoice::class => 'Paiement client',
                        Bill::class => 'Paiement fournisseur',
                    ]),

                Tables\Filters\SelectFilter::make('payment_method_id')
                    ->label('Mode de paiement')
                    ->relationship('paymentMethod', 'name')
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
                            ->when($data['from'], fn ($q) => $q->whereDate('payment_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('payment_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
