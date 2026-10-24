<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use Illuminate\Database\Events\ConnectionEstablished;

// A blocked or runaway transaction must fail with a clear error instead of making every other request wait.
final class AplicarLimitesDeSesion
{
    private const array PARAMETROS = ['lock_timeout', 'statement_timeout', 'idle_in_transaction_session_timeout'];

    public function handle(ConnectionEstablished $evento): void
    {
        $conexion = $evento->connection;

        if ($conexion->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::PARAMETROS as $parametro) {
            $valor = $conexion->getConfig($parametro);

            if (is_string($valor) && $valor !== '') {
                $conexion->select('select set_config(?, ?, false)', [$parametro, $valor]);
            }
        }
    }
}
