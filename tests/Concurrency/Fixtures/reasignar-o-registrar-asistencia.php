<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Actions\RegistrarNotasParciales;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $operacion, $grupoId, $docenteId, $usuarioId, $fecha, $estados] = $argv;
esperarHasta((float) $inicio);

// The roll call or the grades start only once another backend holds a row lock on the group table inside its open transaction.
$hasta = microtime(true) + 10;
$retenido = fn (): bool => DB::table('pg_locks')
    ->join('pg_class', 'pg_class.oid', '=', 'pg_locks.relation')
    ->join('pg_stat_activity', 'pg_stat_activity.pid', '=', 'pg_locks.pid')
    ->where('pg_class.relname', 'aca_grupos')
    ->where('pg_locks.mode', 'RowShareLock')
    ->where('pg_stat_activity.state', 'idle in transaction')
    ->where('pg_locks.pid', '!=', DB::raw('pg_backend_pid()'))
    ->exists();
$visto = $operacion === 'reasignar';
while (! $visto && microtime(true) < $hasta) {
    $visto = $retenido();

    if (! $visto) {
        usleep(50_000);
    }
}

if (! $visto) {
    echo "{$operacion}: la reasignación nunca retuvo el grupo";

    exit(1);
}

$usuario = User::query()->findOrFail((int) $usuarioId);

try {
    if ($operacion === 'reasignar') {
        DB::transaction(function () use ($app, $grupoId, $docenteId, $usuario): void {
            $app->make(AsignarDocente::class)((int) $grupoId, (int) $docenteId, $usuario);
            usleep(1_000_000);
        });
    } elseif ($operacion === 'notas') {
        $app->make(RegistrarNotasParciales::class)((int) $grupoId, (int) $docenteId, json_decode($estados, true), $usuario);
    } else {
        $app->make(RegistrarAsistencia::class)((int) $grupoId, (int) $docenteId, $fecha, '18:00', json_decode($estados, true), $usuario, 'Saludos y presentaciones');
    }
    echo "{$operacion}: hecho";
} catch (ReglaDeNegocioIncumplida $error) {
    echo "{$operacion}: {$error->getMessage()}";
}
