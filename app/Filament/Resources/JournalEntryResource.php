<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?string $navigationLabel = 'Écritures comptables';

    protected static ?string $modelLabel = 'Écriture';

    protected static ?string $pluralModelLabel = 'Écritures comptables';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'écriture')
                    ->schema([
                        Forms\Components\Select::make('journal_id')
                            ->label('Journal')
                            ->relationship('journal', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->maxLength(255)
                            ->placeholder('N° facture, paiement...')
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'posted' => 'Validée',
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status === 'posted')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Lignes d\'écriture')
                    ->schema([
                        Forms\Components\Repeater::make('transactions')
                            ->relationship('transactions')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->label('Compte')
                                    ->relationship('account', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->required()
                                    ->preload()
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('debit')
                                    ->label('Débit')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('FCFA')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state > 0) {
                                            $set('credit', 0);
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('credit')
                                    ->label('Crédit')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('FCFA')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state > 0) {
                                            $set('debit', 0);
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('description')
                                    ->label('Libellé')
                                    ->columnSpan(3),
                            ])
                            ->columns(8)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Ajouter une ligne')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                            $state['account_id']
                                ? \App\Models\Account::find($state['account_id'])?->code
                                : null
                            ),
                    ]),

                Forms\Components\Section::make('Totaux')
                    ->schema([
                        Forms\Components\Placeholder::make('totals')
                            ->label('')
                            ->content(function ($get) {
                                $transactions = $get('transactions') ?? [];
                                $totalDebit = collect($transactions)->sum('debit');
                                $totalCredit = collect($transactions)->sum('credit');
                                $difference = $totalDebit - $totalCredit;

                                $balanceColor = abs($difference) < 0.01 ? 'text-green-600' : 'text-red-600';

                                return new \Illuminate\Support\HtmlString("
                                    <div class='grid grid-cols-3 gap-4 text-sm'>
                                        <div>
                                            <strong>Total Débit:</strong>
                                            <span class='text-green-600'>" . number_format($totalDebit, 2) . " FCFA</span>
                                        </div>
                                        <div>
                                            <strong>Total Crédit:</strong>
                                            <span class='text-red-600'>" . number_format($totalCredit, 2) . " FCFA</span>
                                        </div>
                                        <div>
                                            <strong>Différence:</strong>
                                            <span class='{$balanceColor}'>" . number_format($difference, 2) . " FCFA</span>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_number')
                    ->label('N° Écriture')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('journal.code')
                    ->label('Journal')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('total_debit')
                    ->label('Débit')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_credit')
                    ->label('Crédit')
                    ->money('XOF')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Brouillon',
                        'posted' => 'Validée',
                        'cancelled' => 'Annulée',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'warning',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Validée le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('journal_id')
                    ->label('Journal')
                    ->relationship('journal', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'posted' => 'Validée',
                        'cancelled' => 'Annulée',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->status === 'posted'),

                Tables\Actions\Action::make('post')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (JournalEntry $record) {
                        try {
                            $service = app(AccountingService::class);
                            $service->postEntry($record);

                            Notification::make()
                                ->title('Écriture validée')
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
                    ->hidden(fn ($record) => $record->status === 'posted'),

                Tables\Actions\Action::make('reverse')
                    ->label('Extourner')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (JournalEntry $record) {
                        try {
                            $service = app(AccountingService::class);
                            $service->reverseEntry($record);

                            Notification::make()
                                ->title('Écriture extournée')
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
                    ->hidden(fn ($record) => $record->status !== 'posted'),

                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->status === 'posted'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => true), // Désactiver la suppression en masse
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
