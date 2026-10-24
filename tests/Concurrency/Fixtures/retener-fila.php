<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, , $tabla, $id, $segundos] = $argv;

// Holds the row lock long enough for the test process to hit its lock timeout.
DB::transaction(function () use ($tabla, $id, $segundos): void {
    DB::table($tabla)->where('id', (int) $id)->lockForUpdate()->first();
    DB::select('select pg_sleep(?)', [(float) $segundos]);
});
echo 'liberada';
