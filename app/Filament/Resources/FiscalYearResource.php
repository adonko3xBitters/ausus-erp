<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FiscalYearResource\Pages;
use App\Models\FiscalYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class FiscalYearResource extends Resource
{
    protected static ?string $model = FiscalYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Exercices comptables';

    protected static ?string $modelLabel = 'Exercice comptable';

    protected static ?string $pluralModelLabel = 'Exercices comptables';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'exercice')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Exercice 2025')
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->after('start_date')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Exercice actif')
                            ->helperText('Un seul exercice peut être actif à la fois')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    // Si on active cet exercice, désactiver les autres
                                    FiscalYear::where('is_active', true)->update(['is_active' => false]);
                                }
                            })
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_closed')
                            ->label('Exercice clôturé')
                            ->disabled(fn ($record) => !$record || !$record->is_closed)
                            ->helperText('Un exercice clôturé ne peut plus être modifié')
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

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Durée')
                    ->getStateUsing(function ($record) {
                        $days = $record->start_date->diffInDays($record->end_date) + 1;
                        $months = round($days / 30);
                        return "{$months} mois ({$days} jours)";
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_closed')
                    ->label('Clôturé')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Clôturé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('closedBy.name')
                    ->label('Clôturé par')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Exercice actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),

                Tables\Filters\TernaryFilter::make('is_closed')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Clôturés')
                    ->falseLabel('Ouverts'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->is_closed),

                Tables\Actions\Action::make('close')
                    ->label('Clôturer')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clôturer l\'exercice')
                    ->modalDescription('Êtes-vous sûr de vouloir clôturer cet exercice ? Cette action est irréversible.')
                    ->modalSubmitActionLabel('Oui, clôturer')
                    ->action(function (FiscalYear $record) {
                        $record->update([
                            'is_closed' => true,
                            'is_active' => false,
                            'closed_at' => now(),
                            'closed_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Exercice clôturé')
                            ->success()
                            ->send();
                    })
                    ->hidden(fn ($record) => $record->is_closed),

                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->is_closed),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFiscalYears::route('/'),
            'create' => Pages\CreateFiscalYear::route('/create'),
            'edit' => Pages\EditFiscalYear::route('/{record}/edit'),
        ];
    }
}
