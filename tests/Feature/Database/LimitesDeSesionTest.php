<?php

declare(strict_types=1);

use App\Shared\Support\Database\ConectorPostgres;
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

it('reports a malformed limit and connects without it', function (): void {
    Log::spy();

    $conexion = conexionLimitada(['lock_timeout' => "5s' -c work_mem=1GB", 'statement_timeout' => '30 segundos']);

    expect(limitesDe($conexion))->toBe(['0', '0', '1min'])
        ->and($conexion->selectOne("select current_setting('work_mem') as valor")->valor)->not->toBe('1GB');

    Log::shouldHaveReceived('error')->with('Límite de sesión de PostgreSQL inválido; se usa el valor por defecto.', ['parametro' => 'lock_timeout'])->once();
    Log::shouldHaveReceived('error')->with('Límite de sesión de PostgreSQL inválido; se usa el valor por defecto.', ['parametro' => 'statement_timeout'])->once();
});

it('does not cap statement time for console commands such as migrations and workers', function (): void {
    $pdo = (new ConectorPostgres(enConsola: true))->connect(config('database.connections.pgsql'));

    expect([
        $pdo->query("select current_setting('lock_timeout')")->fetchColumn(),
        $pdo->query("select current_setting('statement_timeout')")->fetchColumn(),
    ])->toBe(['5s', '0']);
});
