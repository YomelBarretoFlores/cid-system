<?php

declare(strict_types=1);

use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $personaId, $grupoId, $pagoId] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $personaId, $grupoId, $pagoId): void {
        $app->make(MatricularEstudiante::class)((int) $personaId, (int) $grupoId, (int) $pagoId);
        // Keeps the enrollment uncommitted so the other request always runs while this one holds its locks.
        usleep(500_000);
    });
    echo 'matriculada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
