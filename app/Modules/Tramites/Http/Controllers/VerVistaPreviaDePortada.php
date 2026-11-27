<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Http\Controllers;

use App\Modules\Tramites\Filament\Pages\EditarContenidoDePortada;
use App\Modules\Tramites\Support\BorradorDePortada;
use App\Modules\Tramites\Support\ContenidoDePortada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class VerVistaPreviaDePortada
{
    public function __invoke(ContenidoDePortada $portada): Response|RedirectResponse
    {
        $borrador = BorradorDePortada::leer();

        if ($borrador === null) {
            return redirect(EditarContenidoDePortada::getUrl(panel: 'admin'));
        }

        return response()->view('tramites::vista-previa-de-portada', [...$portada->conTextos($borrador['textos']), 'fotoPendiente' => $borrador['fotoPendiente']])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
