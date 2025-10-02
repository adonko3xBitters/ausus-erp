<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Sécurité';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'Utilisateur';

    protected static ?string $pluralModelLabel = 'Utilisateurs';

    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'roles.name'];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nom & Prénom(s)' => $record->name ?? '—',
            'Adresse e-mail' => $record->email ?? '—',
            'Numéro de téléphone' => $record->phone ?? '—',
            'Rôles' => $record->roles->isNotEmpty() ? $record->roles->map(fn ($role) => $role->name)->implode(', ') : '—'
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations sur l\'utilisateur')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom & Prénom(s)')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Adresse e-mail')
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'L\'adresse e-mail fera office d\'identifiant pour vous connecter a la plateforme.'),
                                PhoneInput::make('phone')
                                    ->label('Numéro de téléphone')
                                    ->defaultCountry('CI')
                                    ->unique(ignoreRecord: true)
                                    ->required(),
                            ])
                    ]),

                Forms\Components\Section::make('Informations sur le compte')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Mot de passe')
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Pour la sécurité de votre compte, veuillez choisir un mot de passe sûr.')
                                    ->password()
                                    ->required()
                                    ->revealable(),
                                Forms\Components\Select::make('roles')
                                    ->label('Rôles')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status du compte utilisateur')
                                    ->onIcon('heroicon-o-face-smile')
                                    ->offIcon('heroicon-o-no-symbol')
                                    ->onColor('info')
                                    ->offColor('warning')
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Activer / Désactiver le compte.')
                                    ->inline(false)
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom & Prénom(s)')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Adresse e-mail')
                    ->searchable(),
                PhoneColumn::make('phone')
                    ->label('Numéro de téléphone')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->color('info')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
