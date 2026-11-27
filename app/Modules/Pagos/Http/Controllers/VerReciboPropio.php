<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Http\Controllers;

use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Support\ReciboGuardado;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VerReciboPropio
{
    public function __invoke(Request $request, Pago $pago, FichaDelUsuario $fichaDelUsuario): StreamedResponse
    {
        // Not found rather than forbidden, so another student's payment is indistinguishable from a missing one.
        $esPropio = $pago->persona_id === $fichaDelUsuario((int) $request->user()?->getAuthIdentifier())?->personaId;
        $ruta = $esPropio ? ReciboGuardado::ruta($pago) : null;

        abort_if($ruta === null, 404);

        return Storage::disk('local')->response($ruta, ReciboGuardado::nombreDeDescarga($pago, $ruta), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
