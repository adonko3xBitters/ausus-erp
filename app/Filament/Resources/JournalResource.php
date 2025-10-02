<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Models\Journal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?string $navigationLabel = 'Journaux';

    protected static ?string $modelLabel = 'Journal';

    protected static ?string $pluralModelLabel = 'Journaux';

    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du journal')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->placeholder('VT')
                            ->helperText('Code court pour identifier le journal')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Journal des ventes')
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options(Journal::getTypes())
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('default_debit_account_id')
                            ->label('Compte débit par défaut')
                            ->relationship('defaultDebitAccount', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->helperText('Compte débité par défaut pour ce journal')
                            ->columnSpan(1),

                        Forms\Components\Select::make('default_credit_account_id')
                            ->label('Compte crédit par défaut')
                            ->relationship('defaultCreditAccount', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->helperText('Compte crédité par défaut pour ce journal')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => Journal::getTypes()[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'sales' => 'success',
                        'purchases' => 'warning',
                        'bank' => 'info',
                        'cash' => 'primary',
                        'general' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('entries_count')
                    ->label('Écritures')
                    ->counts('entries')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('defaultDebitAccount.code')
                    ->label('Compte débit')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('defaultCreditAccount.code')
                    ->label('Compte crédit')
                    ->badge()
                    ->color('danger')
                    ->toggleable(),

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
                    ->options(Journal::getTypes())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}
