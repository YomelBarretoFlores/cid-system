<?php

declare(strict_types=1);

use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $modo, $grupoId] = $argv;

if ($modo === 'esperar') {
    config(['database.connections.pgsql.lock_timeout' => '500ms']);
    DB::purge();
    DB::select('select 1');
}

esperarHasta((float) $inicio);

// The waiting process starts only once the other one sits in its transaction holding the row lock.
$hasta = microtime(true) + 10;
while ($modo === 'esperar' && microtime(true) < $hasta && ! DB::table('pg_stat_activity')
    ->where('datname', DB::raw('current_database()'))
    ->where('state', 'idle in transaction')
    ->where('pid', '!=', DB::raw('pg_backend_pid()'))
    ->exists()) {
    usleep(50_000);
}

try {
    Transaccion::ejecutar(function () use ($modo, $grupoId): void {
        DB::table('aca_grupos')->where('id', (int) $grupoId)->lockForUpdate()->first();

        if ($modo === 'retener') {
            usleep(2_000_000);
        }
    });
    echo "{$modo}: hecho";
} catch (ReglaDeNegocioIncumplida $error) {
    echo "{$modo}: {$error->getMessage()}";
}
