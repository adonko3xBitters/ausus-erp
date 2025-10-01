<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?string $navigationLabel = 'Plan comptable';

    protected static ?string $modelLabel = 'Compte';

    protected static ?string $pluralModelLabel = 'Comptes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du compte')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('101')
                            ->helperText('Code OHADA du compte')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Capital social')
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'asset' => 'Actif',
                                'liability' => 'Passif',
                                'equity' => 'Capitaux propres',
                                'revenue' => 'Produits',
                                'expense' => 'Charges',
                                'other' => 'Autre',
                            ])
                            ->columnSpan(1),

                        Forms\Components\Select::make('category')
                            ->label('Classe')
                            ->required()
                            ->options([
                                'class_1' => 'Classe 1 - Ressources durables',
                                'class_2' => 'Classe 2 - Actif immobilisé',
                                'class_3' => 'Classe 3 - Stocks',
                                'class_4' => 'Classe 4 - Tiers',
                                'class_5' => 'Classe 5 - Trésorerie',
                                'class_6' => 'Classe 6 - Charges',
                                'class_7' => 'Classe 7 - Produits',
                                'class_8' => 'Classe 8 - Comptes spéciaux',
                                'class_9' => 'Classe 9 - Analytique',
                            ])
                            ->columnSpan(1),

                        Forms\Components\Select::make('parent_id')
                            ->label('Compte parent')
                            ->relationship('parent', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->helperText('Laisser vide pour un compte principal')
                            ->columnSpan(2),

                        Forms\Components\Select::make('currency_id')
                            ->label('Devise')
                            ->relationship('currency', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->helperText('Laisser vide pour utiliser la devise de base')
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_system')
                            ->label('Compte système')
                            ->helperText('Les comptes système ne peuvent pas être supprimés')
                            ->disabled()
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
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Code copié'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn ($record) => $record->parent ? "↳ {$record->parent->code}" : null),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'asset' => 'Actif',
                        'liability' => 'Passif',
                        'equity' => 'Capitaux propres',
                        'revenue' => 'Produits',
                        'expense' => 'Charges',
                        'other' => 'Autre',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'warning',
                        'revenue' => 'info',
                        'expense' => 'gray',
                        'other' => 'secondary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Classe')
                    ->formatStateUsing(fn ($state) => 'Classe ' . substr($state, -1))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency.code')
                    ->label('Devise')
                    ->badge()
                    ->color('primary')
                    ->default('-')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_system')
                    ->label('Système')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'asset' => 'Actif',
                        'liability' => 'Passif',
                        'equity' => 'Capitaux propres',
                        'revenue' => 'Produits',
                        'expense' => 'Charges',
                        'other' => 'Autre',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Classe')
                    ->options([
                        'class_1' => 'Classe 1',
                        'class_2' => 'Classe 2',
                        'class_3' => 'Classe 3',
                        'class_4' => 'Classe 4',
                        'class_5' => 'Classe 5',
                        'class_6' => 'Classe 6',
                        'class_7' => 'Classe 7',
                        'class_8' => 'Classe 8',
                        'class_9' => 'Classe 9',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),

                Tables\Filters\Filter::make('parent_only')
                    ->label('Comptes principaux uniquement')
                    ->query(fn ($query) => $query->whereNull('parent_id'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code', 'asc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
