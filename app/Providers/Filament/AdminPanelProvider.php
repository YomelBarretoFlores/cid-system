<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use App\Shared\Auth\Filament\AutenticacionPorApp;
use App\Shared\Auth\Filament\EditarPerfil;
use App\Shared\Auth\Filament\IngresarAlPanel;
use App\Shared\Auth\Http\Middleware\ExigirDobleFactorSiEsObligatorio;
use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use App\Shared\Support\Filament\Inicio;
use App\Shared\Support\Http\Middleware\AsignarIdDeSolicitud;
use App\Shared\Ui\PaletaCiln;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
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
        SelectFilter::configureUsing(fn (SelectFilter $filtro): SelectFilter => $filtro->native(false));
        Table::configureUsing(fn (Table $tabla): Table => $tabla->defaultSortOptionLabel('Predeterminado'));
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
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('filament.admin.dropdown-sin-cierre-por-teclado'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): View => view('filament.admin.alternar-menu'))
            ->darkMode(false)
            ->topbar(false)
            ->sidebarWidth('268px')
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->globalSearchKeyBindings(['mod+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->login(IngresarAlPanel::class)
            // Behind a high-latency link each full page load costs several round trips, so screens swap in place and are prefetched on hover.
            ->spa(hasPrefetching: true)
            ->spaUrlExceptions(['*/estudiante*', '*/docente*', '*/ingresar*'])
            ->profile(EditarPerfil::class, isSimple: false)
            // isRequired keeps the set-up page and the required hook registered; the setting decides per request whether it applies.
            ->multiFactorAuthentication([AutenticacionPorApp::make()->recoverable()->brandName('CILN - UNASAM')], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(ExigirDobleFactorSiEsObligatorio::class)
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
                Inicio::class,
            ])
            ->navigationGroups(['Atención', 'Oferta académica', 'Docencia', 'Certificación', 'Reportes', 'Configuración'])
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
                ProtegerSesionDelPanel::class,
                EstablecerContextoAuditoria::class,
            ], isPersistent: true);

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
