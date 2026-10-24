<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

afterEach(fn () => DB::purge('pgsql_limitada'));

function conexionLimitada(array $limites): Connection
{
    config(['database.connections.pgsql_limitada' => [...config('database.connections.pgsql'), ...$limites]]);

    return DB::connection('pgsql_limitada');
}

function limitesDe(Connection $conexion): array
{
    $fila = $conexion->selectOne("select current_setting('lock_timeout') as bloqueo, current_setting('statement_timeout') as sentencia, current_setting('idle_in_transaction_session_timeout') as inactiva");

    return [$fila->bloqueo, $fila->sentencia, $fila->inactiva];
}

it('bounds lock waits, statements and idle transactions on the default connection', function (): void {
    expect(limitesDe(DB::connection()))->toBe(['5s', '30s', '1min']);
});

it('applies the limits configured for a connection', function (): void {
    $conexion = conexionLimitada(['lock_timeout' => '1500ms', 'statement_timeout' => '10s', 'idle_in_transaction_session_timeout' => '2min']);

    expect(limitesDe($conexion))->toBe(['1500ms', '10s', '2min']);
});

it('keeps the limits after a reconnection', function (): void {
    $conexion = conexionLimitada(['lock_timeout' => '2s']);
    $conexion->reconnect();

    expect(limitesDe($conexion)[0])->toBe('2s');
});

it('keeps the limits on the write side of a read and write connection', function (): void {
    $conexion = conexionLimitada(['lock_timeout' => '3s', 'read' => ['host' => [config('database.connections.pgsql.host')]], 'write' => ['host' => [config('database.connections.pgsql.host')]]]);

    $escritura = $conexion->getPdo()->query("select current_setting('lock_timeout')")->fetchColumn();
    $lectura = $conexion->getReadPdo()->query("select current_setting('lock_timeout')")->fetchColumn();

    expect([$escritura, $lectura])->toBe(['3s', '3s']);
});

it('leaves the PostgreSQL default when a limit is empty', function (): void {
    expect(limitesDe(conexionLimitada(['lock_timeout' => '', 'statement_timeout' => null])))->toBe(['0', '0', '1min']);
});

it('falls back to the safe default when a limit is malformed and reports it once', function (): void {
    Log::spy();

    $conexion = conexionLimitada(['lock_timeout' => "5s' -c work_mem=1GB", 'statement_timeout' => '30 segundos']);
    $conexion->reconnect();

    expect(limitesDe($conexion))->toBe(['5s', '30s', '1min'])
        ->and($conexion->selectOne("select current_setting('work_mem') as valor")->valor)->not->toBe('1GB');

    Log::shouldHaveReceived('error')->with('Límite de sesión de PostgreSQL inválido; se usa el valor seguro por defecto.', ['parametro' => 'lock_timeout'])->once();
    Log::shouldHaveReceived('error')->with('Límite de sesión de PostgreSQL inválido; se usa el valor seguro por defecto.', ['parametro' => 'statement_timeout'])->once();
});

it('accepts every time unit PostgreSQL accepts', function (): void {
    expect(limitesDe(conexionLimitada(['lock_timeout' => '1d', 'statement_timeout' => '900000us'])))->toBe(['1d', '900ms', '1min']);
});

it('lifts the statement cap only for migrations and seeding, not for workers or other commands', function (string $comando, string $limite): void {
    $argv = $_SERVER['argv'];
    $_SERVER['argv'] = ['artisan', ...explode(' ', $comando)];

    try {
        $pdo = app('db.connector.pgsql')->connect(config('database.connections.pgsql'));
        $valor = $pdo->query("select current_setting('statement_timeout')")->fetchColumn();
        $pdo = null;
    } finally {
        $_SERVER['argv'] = $argv;
    }

    expect($valor)->toBe($limite);
})->with([
    'migrate' => ['migrate', '0'],
    'migrate after a global option' => ['--env=production migrate', '0'],
    'seed' => ['db:seed', '0'],
    'queue worker' => ['queue:work', '30s'],
    'scheduler' => ['schedule:run', '30s'],
    'tests' => ['test', '30s'],
]);
