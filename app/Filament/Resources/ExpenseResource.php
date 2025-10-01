<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Dépenses';

    protected static ?string $navigationLabel = 'Dépenses';

    protected static ?string $modelLabel = 'Dépense';

    protected static ?string $pluralModelLabel = 'Dépenses';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('expense_number')
                            ->label('N° Dépense')
                            ->default(fn () => Expense::generateNumber())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),

                        Forms\Components\Select::make('expense_category_id')
                            ->label('Catégorie')
                            ->relationship('expenseCategory', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable()
                            ->required()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\Select::make('vendor_id')
                            ->label('Fournisseur')
                            ->relationship('vendor', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->vendor_number} - {$record->full_name}")
                            ->searchable(['vendor_number', 'name', 'company_name'])
                            ->preload()
                            ->helperText('Optionnel - Sélectionner si la dépense concerne un fournisseur')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Montants')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant HT')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $taxId = $get('tax_id');
                                if ($taxId) {
                                    $tax = \App\Models\Tax::find($taxId);
                                    if ($tax) {
                                        $taxAmount = $tax->calculate($state);
                                        $set('tax_amount', $taxAmount);
                                        $set('total_amount', $state + $taxAmount);
                                    }
                                } else {
                                    $set('tax_amount', 0);
                                    $set('total_amount', $state);
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\Select::make('tax_id')
                            ->label('Taxe')
                            ->relationship('tax', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $amount = $get('amount') ?? 0;
                                if ($state) {
                                    $tax = \App\Models\Tax::find($state);
                                    if ($tax) {
                                        $taxAmount = $tax->calculate($amount);
                                        $set('tax_amount', $taxAmount);
                                        $set('total_amount', $amount + $taxAmount);
                                    }
                                } else {
                                    $set('tax_amount', 0);
                                    $set('total_amount', $amount);
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('tax_amount')
                            ->label('Montant taxe')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('FCFA')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Montant TTC')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('FCFA')
                            ->extraAttributes(['class' => 'font-bold text-lg'])
                            ->columnSpan(1),

                        Forms\Components\Select::make('currency_id')
                            ->label('Devise')
                            ->relationship('currency', 'code')
                            ->required()
                            ->default(fn () => \App\Models\Currency::where('is_base', true)->first()?->id)
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Paiement')
                    ->schema([
                        Forms\Components\Select::make('payment_method_id')
                            ->label('Mode de paiement')
                            ->relationship('paymentMethod', 'name')
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
                            ->searchable()
                            ->preload()
                            ->helperText('Compte banque ou caisse')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->maxLength(255)
                            ->placeholder('N° facture, reçu, chèque...')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3)
                            ->placeholder('Description détaillée de la dépense...')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('attachments')
                            ->label('Justificatifs')
                            ->multiple()
                            ->directory('expenses/attachments')
                            ->maxSize(5120) // 5 MB
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->helperText('Factures, reçus, photos... (PDF ou images, max 5MB par fichier)')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes internes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'rejected' => 'Rejetée',
                                'paid' => 'Payée',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled(fn ($record) => $record && in_array($record->status, ['approved', 'paid']))
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->rows(2)
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_number')
                    ->label('N° Dépense')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expenseCategory.name')
                    ->label('Catégorie')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->toggleable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'rejected' => 'Rejetée',
                        'paid' => 'Payée',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($record) => $record->status_color),

                Tables\Columns\IconColumn::make('has_attachments')
                    ->label('Justif.')
                    ->getStateUsing(fn ($record) => !empty($record->attachments))
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(fn ($record) => !empty($record->attachments) ? count($record->attachments) . ' fichier(s)' : null),

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
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Catégorie')
                    ->relationship('expenseCategory', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'rejected' => 'Rejetée',
                        'paid' => 'Payée',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('vendor_id')
                    ->label('Fournisseur')
                    ->relationship('vendor', 'name')
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
                            ->when($data['from'], fn ($q) => $q->whereDate('expense_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('expense_date', '<=', $data['until']));
                    }),

                Tables\Filters\TernaryFilter::make('has_attachments')
                    ->label('Avec justificatifs')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('attachments')->where('attachments', '!=', '[]'),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('attachments')->orWhere('attachments', '[]')),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => in_array($record->status, ['approved', 'paid'])),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approuver')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approuver la dépense')
                        ->modalDescription('Êtes-vous sûr de vouloir approuver cette dépense ?')
                        ->modalSubmitActionLabel('Oui, approuver')
                        ->action(function (Expense $record) {
                            try {
                                $record->approve();

                                Notification::make()
                                    ->title('Dépense approuvée')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'pending'),

                    Tables\Actions\Action::make('reject')
                        ->label('Rejeter')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Motif de rejet')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Expense $record, array $data) {
                            try {
                                $record->reject($data['rejection_reason']);

                                Notification::make()
                                    ->title('Dépense rejetée')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'pending'),

                    Tables\Actions\Action::make('mark_as_paid')
                        ->label('Marquer comme payée')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Marquer comme payée')
                        ->modalDescription('Cela générera automatiquement l\'écriture comptable.')
                        ->modalSubmitActionLabel('Oui, marquer comme payée')
                        ->action(function (Expense $record) {
                            try {
                                $record->markAsPaid();

                                Notification::make()
                                    ->title('Dépense marquée comme payée')
                                    ->body('L\'écriture comptable a été générée')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn ($record) => $record->status === 'approved'),

                    Tables\Actions\Action::make('view_journal_entry')
                        ->label('Voir l\'écriture')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn ($record) => $record->journalEntry
                            ? route('filament.admin.resources.journal-entries.view', ['record' => $record->journalEntry->id])
                            : null
                        )
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->journalEntry !== null),

                    Tables\Actions\DeleteAction::make()
                        ->hidden(fn ($record) => in_array($record->status, ['approved', 'paid'])),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => true), // Désactiver la suppression en masse

                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approuver la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->approve();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} dépense(s) approuvée(s)")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('expense_date', 'desc');
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
