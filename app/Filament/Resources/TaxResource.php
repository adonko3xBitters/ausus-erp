<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Taxes';

    protected static ?string $modelLabel = 'Taxe';

    protected static ?string $pluralModelLabel = 'Taxes';

    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la taxe')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('TVA 18%')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('TVA_18')
                            ->helperText('Code unique pour identifier la taxe')
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'percentage' => 'Pourcentage',
                                'fixed' => 'Montant fixe',
                            ])
                            ->default('percentage')
                            ->reactive()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('rate')
                            ->label(fn ($get) => $get('type') === 'percentage' ? 'Taux (%)' : 'Montant')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->suffix(fn ($get) => $get('type') === 'percentage' ? '%' : currency()->symbol)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('tax_account_id')
                            ->label('Compte comptable')
                            ->relationship('taxAccount', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->helperText('Compte où sera comptabilisée cette taxe')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_compound')
                            ->label('Taxe composée')
                            ->helperText('Taxe calculée sur le montant incluant d\'autres taxes')
                            ->columnSpan(1),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state === 'percentage' ? 'Pourcentage' : 'Montant fixe')
                    ->badge()
                    ->color(fn ($state) => $state === 'percentage' ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Taux/Montant')
                    ->formatStateUsing(function ($record) {
                        if ($record->type === 'percentage') {
                            return number_format($record->rate, 2) . ' %';
                        }
                        return number_format($record->rate, 0) . ' ' . currency()->symbol;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('taxAccount.code')
                    ->label('Compte')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_compound')
                    ->label('Composée')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'percentage' => 'Pourcentage',
                        'fixed' => 'Montant fixe',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Toutes')
                    ->trueLabel('Actives')
                    ->falseLabel('Inactives'),
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
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
