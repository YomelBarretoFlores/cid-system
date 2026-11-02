<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Shared\Audit\Filament\Resources\Auditoria\RegistroDeAuditoriaResource;
use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use App\Shared\Auth\Filament\AutenticacionPorApp;
use App\Shared\Auth\Filament\EditarPerfil;
use App\Shared\Auth\Filament\IngresarAlPanel;
use App\Shared\Auth\Filament\Resources\Roles\RolResource;
use App\Shared\Auth\Filament\Resources\Usuarios\UsuarioResource;
use App\Shared\Auth\Http\Middleware\ExigirDobleFactorSiEsObligatorio;
use App\Shared\Auth\Http\Middleware\ProtegerSesionDelPanel;
use App\Shared\Support\Filament\Inicio;
use Filament\Enums\GlobalSearchPosition;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
        $panel = AparienciaDelPanel::aplicar($panel->default()->id('admin')->path('admin'), 'UNASAM · PANEL')
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->globalSearchKeyBindings(['mod+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->login(IngresarAlPanel::class)
            ->spaUrlExceptions(['*/estudiante*', '*/docente*', '*/ingresar*'])
            ->profile(EditarPerfil::class, isSimple: false)
            // isRequired keeps the set-up page and the required hook registered; the setting decides per request whether it applies.
            ->multiFactorAuthentication([AutenticacionPorApp::make()->recoverable()->brandName('CILN - UNASAM')], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(ExigirDobleFactorSiEsObligatorio::class)
            ->strictAuthorization()
            ->pages([
                Inicio::class,
            ])
            // Discovery only scans the modules; the staff accounts and audit screens live in app/Shared.
            ->resources([
                UsuarioResource::class,
                RolResource::class,
                RegistroDeAuditoriaResource::class,
            ])
            ->navigationGroups(['Atención', 'Oferta académica', 'Docencia', 'Certificación', 'Reportes', 'Configuración'])
            ->authMiddleware([
                Authenticate::class,
                ProtegerSesionDelPanel::class,
                EstablecerContextoAuditoria::class,
            ], isPersistent: true);

        return AparienciaDelPanel::descubrir($panel);
    }
}
