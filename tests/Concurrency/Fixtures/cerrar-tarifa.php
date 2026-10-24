<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\CerrarTarifa;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $tarifaId, $hasta, $espera, $modo] = [...$argv, '0', 'action'];
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $tarifaId, $hasta, $espera, $modo): void {
        $modo === 'sql'
            ? DB::table('pag_tarifas')->where('id', (int) $tarifaId)->update(['vigente_hasta' => $hasta])
            : $app->make(CerrarTarifa::class)((int) $tarifaId, CarbonImmutable::parse($hasta));
        // Optionally keeps the closing uncommitted while the other process registers a payment.
        usleep((int) $espera);
    });
    echo 'cerrada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
} catch (QueryException $error) {
    echo str_contains($error->getMessage(), 'no puede cerrar antes de su último pago') ? 'rechazada por la base' : $error->getMessage();
}
