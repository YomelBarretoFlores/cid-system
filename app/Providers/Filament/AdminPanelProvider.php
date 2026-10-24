<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentTimezone::set('America/Lima');
    }

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('CILN - UNASAM')
            ->login()
            ->strictAuthorization()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EstablecerContextoAuditoria::class,
            ]);

        return $this->discoverModules($panel);
    }

    private function discoverModules(Panel $panel): Panel
    {
        foreach (glob(app_path('Modules/*/Filament'), GLOB_ONLYDIR) ?: [] as $directory) {
            $namespace = 'App\\Modules\\'.basename(dirname($directory)).'\\Filament';

            $panel
                ->discoverResources(in: "{$directory}/Resources", for: "{$namespace}\\Resources")
                ->discoverPages(in: "{$directory}/Pages", for: "{$namespace}\\Pages")
                ->discoverWidgets(in: "{$directory}/Widgets", for: "{$namespace}\\Widgets");
        }

        return $panel;
    }
}
