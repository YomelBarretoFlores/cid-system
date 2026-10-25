<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers\Api;

use App\Modules\Certificacion\Actions\GenerarPdfDelEstudiante;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class DescargarMiDocumento
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private GenerarPdfDelEstudiante $generarPdf,
    ) {}

    /**
     * The PDF of one of the student's own valid documents; any other code answers 404.
     */
    public function __invoke(Request $request, string $codigo): PdfBuilder
    {
        $ficha = ($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);

        return ($this->generarPdf)($ficha->personaId, $codigo);
    }
}
