<?php

declare(strict_types=1);

namespace App\Modules\Personas\Http\Controllers\Concerns;

use App\Modules\Personas\Actions\CambiarFotoDePerfil;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait SirveFoto
{
    private function servirFoto(?string $ruta): StreamedResponse
    {
        $disco = Storage::disk(CambiarFotoDePerfil::DISCO);

        abort_unless($ruta !== null && $disco->exists($ruta), 404);

        return $disco->response($ruta, 'foto.'.pathinfo($ruta, PATHINFO_EXTENSION), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
