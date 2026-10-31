<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Shared\Support\Http\Middleware\AsignarIdDeSolicitud;
use App\Shared\Ui\PaletaCiln;
use Filament\Enums\UserMenuPosition;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// The staff and teacher panels share one look and one request pipeline; each panel adds only its own access rules.
final class AparienciaDelPanel
{
    public static function aplicar(Panel $panel, string $subtitulo): Panel
    {
        return $panel
            ->brandName('CILN - UNASAM')
            ->brandLogo(fn (): View => view('filament.admin.marca', ['subtitulo' => $subtitulo]))
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
            ->userMenu(position: UserMenuPosition::Sidebar)
            // Behind a high-latency link each full page load costs several round trips, so screens swap in place and are prefetched on hover.
            ->spa(hasPrefetching: true)
            ->colors([
                'primary' => PaletaCiln::ORO,
                'gray' => PaletaCiln::PIEDRA,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Sky,
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
            ]);
    }

    // Each module keeps its staff screens in Filament/ and its teacher screens in Filament/Docente/, so a panel only finds its own.
    public static function descubrir(Panel $panel, string $subcarpeta = ''): Panel
    {
        foreach (glob(app_path('Modules/*/Filament'.$subcarpeta), GLOB_ONLYDIR) ?: [] as $directory) {
            $modulo = Str::between($directory, app_path('Modules').'/', '/Filament');
            $namespace = "App\\Modules\\{$modulo}\\Filament".str_replace('/', '\\', $subcarpeta);

            $panel
                ->discoverResources(in: "{$directory}/Resources", for: "{$namespace}\\Resources")
                ->discoverPages(in: "{$directory}/Pages", for: "{$namespace}\\Pages")
                ->discoverWidgets(in: "{$directory}/Widgets", for: "{$namespace}\\Widgets");
        }

        return $panel;
    }
}
