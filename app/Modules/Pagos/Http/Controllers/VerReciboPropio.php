<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Http\Controllers;

use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VerReciboPropio
{
    public function __invoke(Request $request, Pago $pago, FichaDelUsuario $fichaDelUsuario): StreamedResponse
    {
        $disco = Storage::disk('local');
        // Not found rather than forbidden, so another student's payment is indistinguishable from a missing one.
        $esPropio = $pago->persona_id === $fichaDelUsuario((int) $request->user()?->getAuthIdentifier())?->personaId;

        abort_unless($esPropio && $disco->exists($pago->recibo_path), 404);

        $extension = pathinfo($pago->recibo_path, PATHINFO_EXTENSION);

        return $disco->response($pago->recibo_path, 'recibo-'.Str::slug($pago->numero_operacion).".{$extension}", [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
