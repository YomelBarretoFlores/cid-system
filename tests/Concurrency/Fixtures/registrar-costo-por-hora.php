<?php

declare(strict_types=1);

use App\Modules\Docencia\Actions\RegistrarCostoPorHora;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $orden, $desde, $costo, $usuarioId] = $argv;
$usuario = User::query()->findOrFail((int) $usuarioId);
esperarHasta((float) $inicio);

// The second process starts only once the first one sits in its open transaction holding the category lock or a lock on the costs table.
$hasta = microtime(true) + 10;
$visto = $orden === 'primero';
while (! $visto && microtime(true) < $hasta) {
    $visto = DB::table('pg_locks')
        ->join('pg_stat_activity', 'pg_stat_activity.pid', '=', 'pg_locks.pid')
        ->leftJoin('pg_class', 'pg_class.oid', '=', 'pg_locks.relation')
        ->where(fn ($bloqueo) => $bloqueo->where('pg_locks.locktype', 'advisory')->orWhere('pg_class.relname', 'doc_costos_hora'))
        ->where('pg_stat_activity.state', 'idle in transaction')
        ->where('pg_locks.pid', '!=', DB::raw('pg_backend_pid()'))
        ->exists();

    if (! $visto) {
        usleep(50_000);
    }
}

if (! $visto) {
    echo "{$orden}: el primer registro nunca retuvo la categoría";

    exit(1);
}

try {
    DB::transaction(function () use ($app, $orden, $desde, $costo, $usuario): void {
        $app->make(RegistrarCostoPorHora::class)(CondicionLaboral::Contratado, $costo, CarbonImmutable::parse($desde), null, $usuario);

        if ($orden === 'primero') {
            usleep(1_000_000);
        }
    });
    echo "{$orden}: hecho";
} catch (ReglaDeNegocioIncumplida $error) {
    echo "{$orden}: {$error->getMessage()}";
}
