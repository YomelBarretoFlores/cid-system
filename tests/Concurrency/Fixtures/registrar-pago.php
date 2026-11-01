<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\RegistrarPago;
use App\Modules\Pagos\Data\RegistroDePago;
use App\Modules\Pagos\Models\MedioDePago;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $personaId, $conceptoId, $fechaPago, $recibo, $espera] = [...$argv, '0'];
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $personaId, $conceptoId, $fechaPago, $recibo, $espera): void {
        $app->make(RegistrarPago::class)(
            new RegistroDePago((int) $personaId, (int) $conceptoId, MedioDePago::idDe('banco_nacion'), 'CONC-'.getmypid(), CarbonImmutable::parse($fechaPago), '150.00'),
            new UploadedFile($recibo, 'recibo.pdf', 'application/pdf', null, true),
        );
        // Optionally keeps the payment uncommitted while the other process changes the fee.
        usleep((int) $espera);
    });
    echo 'registrado';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
