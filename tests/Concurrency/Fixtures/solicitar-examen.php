<?php

declare(strict_types=1);

use App\Modules\Matricula\Actions\SolicitarExamenDeClasificacion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $personaId, $idiomaId, $pagoId] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $personaId, $idiomaId, $pagoId): void {
        $app->make(SolicitarExamenDeClasificacion::class)((int) $personaId, (int) $idiomaId, (int) $pagoId);
        // Keeps the new request uncommitted so the other process must check the payment meanwhile.
        usleep(500_000);
    });
    echo 'solicitada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
