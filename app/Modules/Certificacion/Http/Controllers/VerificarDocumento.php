<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers;

use App\Modules\Certificacion\Data\VerificacionDeDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\CodigoDeDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// The official page a document's QR opens: public, rate-limited and kept out of search engines.
final readonly class VerificarDocumento
{
    public function __invoke(Request $request, ?string $codigo = null): Response|RedirectResponse
    {
        $buscado = $request->query('codigo');

        if ($codigo === null && is_string($buscado) && trim($buscado) !== '') {
            return redirect()->route('verificacion.documento', CodigoDeDocumento::normalizar($buscado));
        }

        $documento = $codigo === null ? null : Documento::query()->where('codigo', CodigoDeDocumento::normalizar($codigo))->first();

        return response()
            ->view('certificacion::verificacion', [
                'codigo' => $codigo === null ? null : CodigoDeDocumento::normalizar($codigo),
                'verificacion' => $documento === null ? null : VerificacionDeDocumento::desde($documento),
            ], $codigo !== null && $documento === null ? 404 : 200)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
