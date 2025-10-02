<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use App\Models\Country;
use App\Models\Currency;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class CompanySettings extends Page implements Forms\Contracts\HasForms
{
    use HasPageShield;

    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Entreprise';

    protected static string $view = 'filament.pages.company-settings';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::first();

        if ($settings) {
            $this->form->fill($settings->toArray());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Nom de l\'entreprise')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Select::make('legal_form')
                            ->label('Forme juridique')
                            ->options([
                                'SARL' => 'SARL',
                                'SA' => 'SA',
                                'SAS' => 'SAS',
                                'SASU' => 'SASU',
                                'EI' => 'Entreprise Individuelle',
                                'SNC' => 'SNC',
                                'GIE' => 'GIE',
                            ])
                            ->searchable()
                            ->columnSpan(1),

                        Forms\Components\Select::make('fiscal_regime')
                            ->label('Régime fiscal')
                            ->options([
                                'Réel Normal' => 'Réel Normal',
                                'Réel Simplifié' => 'Réel Simplifié',
                                'Régime de la Synthèse' => 'Régime de la Synthèse',
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Identification fiscale')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Numéro IFU / NIF')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('trade_register')
                            ->label('RCCM')
                            ->maxLength(255)
                            ->helperText('Registre du Commerce et du Crédit Mobilier')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('fiscal_year_end')
                            ->label('Date de clôture habituelle')
                            ->native(false)
                            ->displayFormat('d/m')
                            ->helperText('Jour et mois de clôture des exercices')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Coordonnées')
                    ->schema([
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

                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('postal_code')
                            ->label('Code postal')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\Select::make('country_id')
                            ->label('Pays')
                            ->options(Country::pluck('name', 'id'))
                            // ->relationship('country', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Paramètres comptables')
                    ->schema([
                        Forms\Components\Select::make('currency_id')
                            ->label('Devise de base')
                            ->options(Currency::pluck('name', 'id'))
                            // ->relationship('currency', 'name')
                            // ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Devise principale pour la comptabilité')
                            ->columnSpan(1),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Logo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo de l\'entreprise')
                            ->image()
                            ->imageEditor()
                            ->directory('logos')
                            ->maxSize(2048)
                            ->helperText('Format: PNG, JPG, SVG (Max: 2MB)')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = CompanySetting::first();

        if ($settings) {
            $settings->update($data);
        } else {
            CompanySetting::create($data);
        }

        Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Enregistrer')
                ->submit('save'),
        ];
    }
}
