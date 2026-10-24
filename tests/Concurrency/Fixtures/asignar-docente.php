<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\AsignarDocente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $grupoId, $docenteId, $usuarioId] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $grupoId, $docenteId, $usuarioId): void {
        $app->make(AsignarDocente::class)((int) $grupoId, (int) $docenteId, User::query()->findOrFail((int) $usuarioId));
        // Holds the group and teacher rows so the other process must run while this one is inside its transaction.
        usleep(500_000);
    });
    echo 'asignado';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
