<?php

declare(strict_types=1);

use App\Shared\Audit\Http\Middleware\EstablecerContextoAuditoria;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', EstablecerContextoAuditoria::class);
        $middleware->appendToGroup('api', EstablecerContextoAuditoria::class);

        // The admin panel redirects its own guests to its login; every other web guest goes to the portal login.
        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => $request->is('api/*') ? null : route('estudiante.ingresar'),
        );
        $middleware->redirectUsersTo(fn (): string => route('estudiante.inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
