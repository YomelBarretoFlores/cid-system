<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('bounds lock waits, statements and idle transactions on every PostgreSQL connection', function (): void {
    $valor = fn (string $parametro): string => DB::selectOne("select current_setting('{$parametro}') as valor")->valor;

    expect([$valor('lock_timeout'), $valor('statement_timeout'), $valor('idle_in_transaction_session_timeout')])
        ->toBe(['5s', '30s', '1min']);
});

it('applies the limits configured for a connection when it opens', function (): void {
    config([
        'database.connections.pgsql_limitada' => [
            ...config('database.connections.pgsql'),
            'lock_timeout' => '1500ms',
            'statement_timeout' => '10s',
            'idle_in_transaction_session_timeout' => '2min',
        ],
    ]);

    $conexion = DB::connection('pgsql_limitada');

    expect([
        $conexion->selectOne("select current_setting('lock_timeout') as valor")->valor,
        $conexion->selectOne("select current_setting('statement_timeout') as valor")->valor,
        $conexion->selectOne("select current_setting('idle_in_transaction_session_timeout') as valor")->valor,
    ])->toBe(['1500ms', '10s', '2min']);

    DB::purge('pgsql_limitada');
});
