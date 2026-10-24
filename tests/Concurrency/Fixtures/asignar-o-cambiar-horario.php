<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarHorario;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $operacion, $grupoId, $docenteOHorarioId, $usuarioId] = $argv;
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $operacion, $grupoId, $docenteOHorarioId, $usuarioId): void {
        $operacion === 'asignar'
            ? $app->make(AsignarDocente::class)((int) $grupoId, (int) $docenteOHorarioId, User::query()->findOrFail((int) $usuarioId))
            : $app->make(GuardarHorario::class)((int) $grupoId, 1, '09:00', '11:00', (int) $docenteOHorarioId);
        usleep(500_000);
    });
    echo "{$operacion}: hecho";
} catch (ReglaDeNegocioIncumplida $error) {
    echo "{$operacion}: {$error->getMessage()}";
}
