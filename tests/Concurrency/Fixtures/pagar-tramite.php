<?php

declare(strict_types=1);

use App\Modules\Tramites\Actions\AplicarPagoValidado;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $modo, $personaId, $tipoId, $pagoId] = $argv;
esperarHasta((float) $inicio);

$resultado = DB::transaction(function () use ($app, $modo, $personaId, $tipoId, $pagoId): string {
    $tramite = $modo === 'solicitar'
        ? $app->make(SolicitarTramite::class)((int) $personaId, (int) $tipoId, new DatosDeSolicitud)
        : $app->make(AplicarPagoValidado::class)((int) $pagoId);
    // Keeps the change uncommitted so the other process must look at the payment meanwhile.
    usleep(500_000);

    return $tramite?->pago_id === (int) $pagoId ? "{$modo}: con el pago" : "{$modo}: sin el pago";
});

echo $resultado;
