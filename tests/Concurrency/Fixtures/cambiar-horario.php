<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarHorario;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $grupoId, $horarioId, $horaInicio, $horaFin] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $grupoId, $horarioId, $horaInicio, $horaFin): void {
        $app->make(GuardarHorario::class)((int) $grupoId, 1, $horaInicio, $horaFin, (int) $horarioId);
        usleep(500_000);
    });
    echo 'cambiado';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
