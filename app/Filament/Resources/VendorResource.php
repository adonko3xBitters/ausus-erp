<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Achats';

    protected static ?string $navigationLabel = 'Fournisseurs';

    protected static ?string $modelLabel = 'Fournisseur';

    protected static ?string $pluralModelLabel = 'Fournisseurs';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('vendor_number')
                            ->label('N° Fournisseur')
                            ->default(fn () => Vendor::generateNumber())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom du contact')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Raison sociale')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('mobile')
                            ->label('Mobile')
                            ->tel()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('tax_number')
                            ->label('NIF / IFU')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('trade_register')
                            ->label('RCCM')
                            ->maxLength(255)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('postal_code')
                            ->label('Code postal')
                            ->columnSpan(1),

                        Forms\Components\Select::make('country_id')
                            ->label('Pays')
                            ->relationship('country', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Paramètres comptables')
                    ->schema([
                        Forms\Components\Select::make('currency_id')
                            ->label('Devise')
                            ->relationship('currency', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\Select::make('account_id')
                            ->label('Compte auxiliaire')
                            ->relationship('account', 'name', fn ($query) => $query->where('code', 'like', '401%'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->helperText('Laisser vide pour utiliser le compte 401 par défaut')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('payment_terms')
                            ->label('Délai de paiement')
                            ->numeric()
                            ->default(30)
                            ->suffix('jours')
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes internes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vendor_number')
                    ->label('N° Fournisseur')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->company_name),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('country.name')
                    ->label('Pays')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Solde')
                    ->money('XOF')
                    ->getStateUsing(fn ($record) => $record->balance)
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),
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
            ->defaultSort('vendor_number', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'view' => Pages\ViewVendor::route('/{record}'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
