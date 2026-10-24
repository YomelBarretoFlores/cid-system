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

esperarHasta((float) $inicio + ($modo === 'esperar' ? 0.3 : 0));

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
