<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $operacion, $grupoId, $docenteId, $usuarioId, $fecha, $estados] = $argv;
esperarHasta((float) $inicio);

// The roll call starts only once the reassignment sits in its transaction holding the group lock.
$hasta = microtime(true) + 10;
while ($operacion === 'registrar' && microtime(true) < $hasta && ! DB::table('pg_stat_activity')
    ->where('datname', DB::raw('current_database()'))
    ->where('state', 'idle in transaction')
    ->where('pid', '!=', DB::raw('pg_backend_pid()'))
    ->exists()) {
    usleep(50_000);
}

$usuario = User::query()->findOrFail((int) $usuarioId);

try {
    if ($operacion === 'reasignar') {
        DB::transaction(function () use ($app, $grupoId, $docenteId, $usuario): void {
            $app->make(AsignarDocente::class)((int) $grupoId, (int) $docenteId, $usuario);
            usleep(1_000_000);
        });
    } else {
        $app->make(RegistrarAsistencia::class)((int) $grupoId, (int) $docenteId, $fecha, '18:00', json_decode($estados, true), $usuario);
    }
    echo "{$operacion}: hecho";
} catch (ReglaDeNegocioIncumplida $error) {
    echo "{$operacion}: {$error->getMessage()}";
}
