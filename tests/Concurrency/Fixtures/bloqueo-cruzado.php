<?php

declare(strict_types=1);

use App\Shared\Support\Database\Transaccion;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $primeroId, $segundoId] = $argv;
esperarHasta((float) $inicio);

// Locks two rows in opposite orders on purpose, so PostgreSQL picks a deadlock victim that must be retried.
Transaccion::ejecutar(function () use ($primeroId, $segundoId): void {
    DB::table('aca_grupos')->where('id', (int) $primeroId)->increment('matriculados');
    usleep(300_000);
    DB::table('aca_grupos')->where('id', (int) $segundoId)->increment('matriculados');
});
echo 'hecho';
