<?php

declare(strict_types=1);

use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $modo, $id, $docenteId, $usuarioId] = $argv;
$usuario = User::query()->findOrFail((int) $usuarioId);
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $modo, $id, $docenteId, $usuario): void {
        match ($modo) {
            'cerrar' => $app->make(CerrarActa::class)((int) $id, (int) $docenteId, $usuario),
            default => $app->make(AutorizarRectificacion::class)((int) $id, 'Corrección del examen final.', $usuario),
        };
        // Holds the group or acta lock so the other process must run while this one is inside its transaction.
        usleep(500_000);
    });
    echo 'hecho';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
