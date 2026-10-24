<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

afterEach(function (): void {
    $this->truncateTablesForAllConnections();
});

it('gives the last vacancy to exactly one of two simultaneous requests', function (): void {
    $grupo = Grupo::factory()->for(Periodo::factory()->conMatriculaAbierta())->create(['cupo_maximo' => 1, 'cupo_minimo' => 1]);
    $conexion = config('database.connections.pgsql');
    $inicio = microtime(true) + 2;

    $resultados = Process::pool(fn (Pool $pool) => collect(range(1, 2))->each(fn () => $pool
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
        ->command([PHP_BINARY, 'tests/Concurrency/Fixtures/reservar-vacante.php', (string) $grupo->id, (string) $inicio])))
        ->start()
        ->wait();

    $salidas = collect($resultados)->map(fn ($resultado): string => trim($resultado->output().$resultado->errorOutput()))->sort()->values()->all();

    expect($salidas)->toBe(['El grupo no tiene vacantes disponibles.', 'reservada'])
        ->and($grupo->refresh()->matriculados)->toBe(1);
});
