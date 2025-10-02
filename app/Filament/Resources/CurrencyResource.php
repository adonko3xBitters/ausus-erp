<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Devises';

    protected static ?string $modelLabel = 'Devise';

    protected static ?string $pluralModelLabel = 'Devises';

    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la devise')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(3)
                            ->unique(ignoreRecord: true)
                            ->placeholder('XOF')
                            ->helperText('Code ISO 4217')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Franc CFA (BCEAO)')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('symbol')
                            ->label('Symbole')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('FCFA')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('decimal_places')
                            ->label('Décimales')
                            ->required()
                            ->numeric()
                            ->default(2)
                            ->minValue(0)
                            ->maxValue(4)
                            ->helperText('Nombre de décimales après la virgule')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_base')
                            ->label('Devise de base')
                            ->helperText('Cette devise sera utilisée comme référence pour les taux de change')
                            ->columnSpan(1)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    // Si on active is_base, désactiver les autres
                                    Currency::where('is_base', true)->update(['is_base' => false]);
                                }
                            }),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbole')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('decimal_places')
                    ->label('Décimales')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_base')
                    ->label('Base')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_base')
                    ->label('Devise de base')
                    ->placeholder('Toutes')
                    ->trueLabel('Base uniquement')
                    ->falseLabel('Autres'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Toutes')
                    ->trueLabel('Actives')
                    ->falseLabel('Inactives'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->is_base),
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
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
