<?php

declare(strict_types=1);

use App\Modules\Docencia\Http\Middleware\RequierePerfilDocente;
use App\Modules\Personas\Http\Middleware\RequiereFichaDeEstudiante;
use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use App\Shared\Auth\Http\Middleware\ProtegerDescargaDelPanel;
use App\Shared\Auth\Http\Middleware\RequiereCorreoVerificado;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\DestinoTrasIngreso;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Http\Middleware\AsignarIdDeSolicitud;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
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
        $middleware->encryptCookies(except: ['ciln-menu-colapsado']);
        $middleware->alias(['estudiante' => RequiereFichaDeEstudiante::class, 'docente' => RequierePerfilDocente::class, 'correo.verificado' => RequiereCorreoVerificado::class, 'panel.sesion' => ProtegerDescargaDelPanel::class]);

        // The admin panel redirects its own guests to its login; staff downloads under /panel go there too, every portal page to the single entry.
        $middleware->redirectGuestsTo(fn (Request $request): ?string => match (true) {
            $request->is('api/*') => null,
            $request->is('panel/*') => route('filament.admin.auth.login'),
            default => route('ingresar'),
        });
        // Already signed in, staff opening the entry go to the panel; only a fresh portal sign-in puts the teacher and student modules first (DestinoTrasIngreso).
        $middleware->redirectUsersTo(fn (Request $request): string => match (true) {
            ! $request->user() instanceof User => route('estudiante.inicio'),
            $request->user()->can(User::ADMIN_PANEL_PERMISSION) => route('filament.admin.pages.dashboard'),
            default => app(DestinoTrasIngreso::class)->para($request->user()),
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
        // On the API a business refusal is the client's to fix: 422 with the same message the screens show.
        $exceptions->render(fn (ReglaDeNegocioIncumplida $error, Request $request): ?JsonResponse => $request->is('api/*')
            ? response()->json(['message' => $error->getMessage()], 422)
            : null);
    })->create();
