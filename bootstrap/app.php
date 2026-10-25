<?php

declare(strict_types=1);

use App\Modules\Docencia\Http\Middleware\RequierePerfilDocente;
use App\Modules\Personas\Http\Middleware\RequiereFichaDeEstudiante;
use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use App\Shared\Auth\Enums\EntradaDelPortal;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\DestinoTrasIngreso;
use App\Shared\Support\Http\Middleware\AsignarIdDeSolicitud;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('web', AsignarIdDeSolicitud::class);
        $middleware->prependToGroup('api', AsignarIdDeSolicitud::class);
        $middleware->appendToGroup('web', EstablecerContextoAuditoria::class);
        $middleware->appendToGroup('api', EstablecerContextoAuditoria::class);
        $middleware->throttleApi();
        $middleware->alias(['estudiante' => RequiereFichaDeEstudiante::class, 'docente' => RequierePerfilDocente::class]);

        // The admin panel redirects its own guests to its login; teacher pages go to the teacher entry, the rest to the student entry.
        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => $request->is('api/*') ? null : route(EntradaDelPortal::delPedido($request)->ruta()),
        );
        // Signed-in staff opening an entry page keep going to the panel, as before the teacher entry existed.
        $middleware->redirectUsersTo(fn (Request $request): string => match (true) {
            ! $request->user() instanceof User => route('estudiante.inicio'),
            $request->user()->can(User::ADMIN_PANEL_PERMISSION) => route('filament.admin.pages.dashboard'),
            default => app(DestinoTrasIngreso::class)->para($request->user(), EntradaDelPortal::delPedido($request)),
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
