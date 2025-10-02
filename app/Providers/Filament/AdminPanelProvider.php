<?php

namespace App\Providers\Filament;

use App\Filament\Resources\BillResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\VendorResource;
use App\Models\User;
use Awcodes\FilamentQuickCreate\QuickCreatePlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use lockscreen\FilamentLockscreen\Http\Middleware\Locker;
use lockscreen\FilamentLockscreen\Http\Middleware\LockerTimer;
use lockscreen\FilamentLockscreen\Lockscreen;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
            ])
            ->theme(asset('css/filament/admin/theme.css'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                LockerTimer::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                Locker::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),

                BreezyCore::make()
                    ->myProfile(),

                QuickCreatePlugin::make()
                    ->sort(false)
                    ->keyBindings(['command+shift+a', 'ctrl+shift+a'])
                    ->alwaysShowModal()
                    ->includes([
                        CustomerResource::class,
                        VendorResource::class,
                        ProductResource::class,
                        InvoiceResource::class,
                        BillResource::class,
                        PaymentResource::class,
                        ExpenseResource::class,
                    ]),

                GlobalSearchModalPlugin::make()
                    ->closeButton(enabled: true)
                    ->slideOver(),

                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users(fn () => User::pluck('email', 'name')->toArray()),

                Lockscreen::make(),
            ])
            ->navigationGroups([
                'Configuration',
                'Comptabilité',
                'Ventes',
                'Achats',
                'Stocks',
                'Rapports',
                'Dépenses',
                'Sécurité'
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k']);
    }
}
