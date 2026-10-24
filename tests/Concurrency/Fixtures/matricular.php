<?php

declare(strict_types=1);

use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $personaId, $grupoId, $pagoId] = $argv;
time_sleep_until((float) $inicio);

try {
    $app->make(MatricularEstudiante::class)((int) $personaId, (int) $grupoId, (int) $pagoId);
    echo 'matriculada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
