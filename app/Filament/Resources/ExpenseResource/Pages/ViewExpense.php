<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->hidden(fn ($record) => in_array($record->status, ['approved', 'paid'])),

            Actions\Action::make('approve')
                ->label('Approuver')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $this->record->approve();

                        \Filament\Notifications\Notification::make()
                            ->title('Dépense approuvée')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\Action::make('mark_as_paid')
                ->label('Marquer comme payée')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $this->record->markAsPaid();

                        \Filament\Notifications\Notification::make()
                            ->title('Dépense marquée comme payée')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'approved'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations générales')
                    ->schema([
                        Infolists\Components\TextEntry::make('expense_number')
                            ->label('N° Dépense')
                            ->badge()
                            ->color('primary')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('expense_date')
                            ->label('Date')
                            ->date('d/m/Y')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('expenseCategory.full_name')
                            ->label('Catégorie')
                            ->badge()
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('vendor.full_name')
                            ->label('Fournisseur')
                            ->default('-')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'rejected' => 'Rejetée',
                                'paid' => 'Payée',
                                default => $state,
                            })
                            ->badge()
                            ->color(fn ($record) => $record->status_color)
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Montants')
                    ->schema([
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Montant HT')
                            ->money('XOF'),

                        Infolists\Components\TextEntry::make('tax.name')
                            ->label('Taxe')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('tax_amount')
                            ->label('Montant taxe')
                            ->money('XOF'),

                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Montant TTC')
                            ->money('XOF')
                            ->weight('bold')
                            ->size('lg'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Paiement')
                    ->schema([
                        Infolists\Components\TextEntry::make('paymentMethod.name')
                            ->label('Mode de paiement')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('account.display_name')
                            ->label('Compte')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('reference')
                            ->label('Référence')
                            ->default('-'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Détails')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes internes')
                            ->default('-')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Justificatifs')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('attachments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('file')
                                    ->label('Fichier')
                                    ->getStateUsing(fn ($state) => basename($state))
                                    ->url(fn ($state) => \Storage::url($state))
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-paper-clip')
                                    ->color('primary'),
                            ])
                            ->columns(1)
                            ->visible(fn ($record) => !empty($record->attachments)),

                        Infolists\Components\TextEntry::make('no_attachments')
                            ->label('')
                            ->default('Aucun justificatif')
                            ->color('gray')
                            ->visible(fn ($record) => empty($record->attachments)),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Approbation')
                    ->schema([
                        Infolists\Components\TextEntry::make('approvedBy.name')
                            ->label('Approuvée par')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('approved_at')
                            ->label('Approuvée le')
                            ->dateTime('d/m/Y H:i')
                            ->default('-'),

                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->color('danger')
                            ->visible(fn ($record) => $record->status === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => in_array($record->status, ['approved', 'rejected', 'paid'])),

                Infolists\Components\Section::make('Traçabilité')
                    ->schema([
                        Infolists\Components\TextEntry::make('createdBy.name')
                            ->label('Créé par'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Modifié le')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
