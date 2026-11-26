<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Http\Controllers;

use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\PresentacionDelFut;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

// The printable receipt replaces the detachable CARGO of the paper FUT.
final class VerCargoDelTramite
{
    public function __invoke(Request $request, Tramite $tramite, FichaDelUsuario $fichaDelUsuario): View
    {
        $estudiante = $fichaDelUsuario((int) $request->user()?->getAuthIdentifier());

        // Not found rather than forbidden, so another student's trámite is indistinguishable from a missing one.
        abort_unless($estudiante instanceof FichaDeEstudiante && $tramite->persona_id === $estudiante->personaId, 404);

        return view('tramites::cargo', [
            'tramite' => $tramite,
            'estudiante' => $estudiante,
            'filas' => PresentacionDelFut::filas($tramite->datos),
            'adjuntos' => $tramite->adjuntos()->count(),
        ]);
    }
}
