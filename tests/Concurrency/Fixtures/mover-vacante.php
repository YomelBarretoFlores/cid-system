<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\TrasladoDeVacante;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $origenId, $destinoId] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $origenId, $destinoId): void {
        $app->make(TrasladoDeVacante::class)((int) $origenId, (int) $destinoId);
        // Holds both group rows so the other process must run while this one is inside its transaction.
        usleep(500_000);
    });
    echo 'trasladada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
