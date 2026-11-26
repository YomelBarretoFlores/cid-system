<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Http\Controllers;

use App\Modules\Tramites\Models\Adjunto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DescargarAdjuntoDelTramite
{
    public function __invoke(Adjunto $adjunto): StreamedResponse
    {
        $disco = Storage::disk('local');

        abort_unless($disco->exists($adjunto->path), 404);

        $tramite = $adjunto->tramite()->value('codigo');
        $extension = pathinfo($adjunto->path, PATHINFO_EXTENSION);

        return $disco->download($adjunto->path, Str::slug("{$tramite} {$adjunto->nombre}").".{$extension}", [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
