<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Modules\Docencia\Http\Middleware\AutenticarAlPanelDocente;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use Filament\Actions\Action;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

// Teachers sign in through /ingresar like students; the panel keeps the portal session and asks for no second factor.
final class DocentePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = AparienciaDelPanel::aplicar($panel->id('docente')->path('docente'), 'PORTAL DOCENTE', 'docente')
            ->spaUrlExceptions(['*/admin*', '*/estudiante*', '*/ingresar*'])
            ->userMenuItems([
                Action::make('portalDelEstudiante')
                    ->label('Mi portal de estudiante')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->url(fn (): string => route('estudiante.inicio'))
                    ->visible(fn (): bool => app(FichaDelUsuario::class)((int) auth()->id()) !== null),
            ])
            ->authMiddleware([
                AutenticarAlPanelDocente::class,
                EnsureEmailIsVerified::class,
                EstablecerContextoAuditoria::class,
            ], isPersistent: true);

        return AparienciaDelPanel::descubrir($panel, '/Docente');
    }
}
