<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\RegistrarTarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $conceptoId, $desde, $espera] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $conceptoId, $desde, $espera): void {
        $app->make(RegistrarTarifa::class)((int) $conceptoId, TipoVinculo::PublicoGeneral, '180.00', CarbonImmutable::parse($desde));
        // Keeps the new fee and the closing of the old one uncommitted while the other process registers a payment.
        usleep((int) $espera);
    });
    echo 'tarifa registrada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
