<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers;

use App\Modules\Certificacion\Actions\GenerarPreActaPdf;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Support\AccesoDelDocente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\PdfBuilder;

// The group's teacher (teacher panel) or coordination with the actas permission (staff panel); anyone else gets 404.
final readonly class DescargarPreActa
{
    public function __invoke(Request $request, int $grupo, AccesoDelDocente $acceso, GenerarPreActaPdf $generar): PdfBuilder
    {
        if ($request->routeIs('docente.preacta.pdf')) {
            $acceso->asegurar((int) $request->user()?->id, $grupo);
        } else {
            abort_unless($request->user()?->can(PermisoCertificacion::Actas->value) === true, 403);
        }

        try {
            return $generar($grupo);
        } catch (ReglaDeNegocioIncumplida) {
            abort(404);
        }
    }
}
