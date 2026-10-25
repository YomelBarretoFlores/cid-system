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
            return $this->buscar($buscado);
        }

        $codigo = $codigo === null ? null : CodigoDeDocumento::normalizar($codigo);
        $documento = $codigo === null ? null : Documento::query()->where('codigo', $codigo)->first();

        return response()
            ->view('certificacion::verificacion', [
                'codigo' => $codigo,
                'verificacion' => $documento === null ? null : VerificacionDeDocumento::desde($documento),
            ], $codigo !== null && $documento === null ? 404 : 200)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    // The form sends the typed code as a query; it moves to the canonical URL so the result can be shared.
    private function buscar(string $buscado): RedirectResponse
    {
        $normalizado = CodigoDeDocumento::normalizar($buscado);

        return preg_match('/^[A-Z0-9-]{1,30}$/', $normalizado) === 1
            ? redirect()->route('verificacion.documento', $normalizado)
            : redirect()->route('verificacion.documento');
    }
}
