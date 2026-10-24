<?php

declare(strict_types=1);

use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

/**
 * @param  list<list<int|string>>  $argumentosPorProceso
 * @return list<string>
 */
function enParalelo(string $fixture, array $argumentosPorProceso): array
{
    $conexion = config('database.connections.pgsql');
    $inicio = (string) (microtime(true) + 2);

    $resultados = Process::pool(fn (Pool $pool) => collect($argumentosPorProceso)->each(fn (array $argumentos) => $pool
        ->path(base_path())
        ->env([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => $conexion['host'],
            'DB_PORT' => (string) $conexion['port'],
            'DB_DATABASE' => $conexion['database'],
            'DB_USERNAME' => $conexion['username'],
            'DB_PASSWORD' => $conexion['password'],
        ])
        ->command([PHP_BINARY, "tests/Concurrency/Fixtures/{$fixture}", $inicio, ...array_map(strval(...), $argumentos)])))
        ->start()
        ->wait();

    return collect($resultados)->map(fn ($resultado): string => trim($resultado->output().$resultado->errorOutput()))->sort()->values()->all();
}
