<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use App\Shared\Auth\Filament\EditarPerfil;
use App\Shared\Support\Http\Middleware\AsignarIdDeSolicitud;
use App\Shared\Ui\PaletaCiln;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentTimezone::set('America/Lima');
        DatePicker::configureUsing(fn (DatePicker $campo): DatePicker => $campo->native(false)->format('Y-m-d')->displayFormat('j M Y')->firstDayOfWeek(1)->prefixIcon(Heroicon::OutlinedCalendar, isInline: true));
        Select::configureUsing(fn (Select $campo): Select => $campo->native(false));
    }

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('CILN - UNASAM')
            ->brandLogo(fn (): Factory|\Illuminate\Contracts\View\View => view('filament.admin.marca'))
            ->brandLogoHeight('auto')
            ->favicon(asset('favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Figtree', provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => Vite::fonts()->toHtml())
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): View => view('filament.admin.alternar-menu'))
            ->darkMode(false)
            ->topbar(false)
            ->sidebarWidth('268px')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->login()
            ->profile(EditarPerfil::class, isSimple: false)
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()->brandName('CILN - UNASAM')], isRequired: true)
            ->strictAuthorization()
            ->colors([
                'primary' => PaletaCiln::ORO,
                'gray' => PaletaCiln::PIEDRA,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Sky,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                AsignarIdDeSolicitud::class,
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
