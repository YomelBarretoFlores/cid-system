<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\EliminarDisponibilidad;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $operacion, $docenteId, $objetivoId, $usuarioId] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $operacion, $docenteId, $objetivoId, $usuarioId): void {
        $operacion === 'asignar'
            ? $app->make(AsignarDocente::class)((int) $objetivoId, (int) $docenteId, User::query()->findOrFail((int) $usuarioId))
            : $app->make(EliminarDisponibilidad::class)((int) $docenteId, (int) $objetivoId);
        usleep(500_000);
    });
    echo "{$operacion}: hecho";
} catch (ReglaDeNegocioIncumplida $error) {
    echo "{$operacion}: {$error->getMessage()}";
}
