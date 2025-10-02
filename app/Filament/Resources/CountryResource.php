<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryResource\Pages;
use App\Models\Country;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Pays';

    protected static ?string $modelLabel = 'Pays';

    protected static ?string $pluralModelLabel = 'Pays';

    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du pays')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code ISO')
                            ->required()
                            ->maxLength(2)
                            ->unique(ignoreRecord: true)
                            ->placeholder('CI')
                            ->helperText('Code ISO 3166-1 alpha-2')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom du pays')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Côte d\'Ivoire')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('currency_code')
                            ->label('Code devise')
                            ->required()
                            ->maxLength(3)
                            ->placeholder('XOF')
                            ->helperText('Code ISO 4217')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('phone_code')
                            ->label('Indicatif téléphonique')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('+225')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_ohada')
                            ->label('Pays OHADA')
                            ->helperText('Le pays applique le système comptable OHADA')
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
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Pays')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Devise')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('phone_code')
                    ->label('Indicatif'),

                Tables\Columns\IconColumn::make('is_ohada')
                    ->label('OHADA')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_ohada')
                    ->label('Pays OHADA')
                    ->placeholder('Tous')
                    ->trueLabel('OHADA uniquement')
                    ->falseLabel('Non OHADA uniquement'),

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
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
