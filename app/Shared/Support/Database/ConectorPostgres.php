<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Facades\Log;

// Session limits travel in the connection string, so every PDO (read, write, reconnected) gets them.
final class ConectorPostgres extends PostgresConnector
{
    private const array LIMITES = ['lock_timeout', 'statement_timeout', 'idle_in_transaction_session_timeout'];

    public function __construct(private readonly bool $enConsola = false) {}

    /**
     * @param  array<string, mixed>  $config
     */
    protected function getDsn(array $config): string
    {
        $opciones = [];

        foreach (self::LIMITES as $parametro) {
            $valor = $this->valor($config, $parametro);

            if ($valor !== null) {
                $opciones[] = "-c {$parametro}={$valor}";
            }
        }

        return parent::getDsn($config).($opciones === [] ? '' : ";options='".implode(' ', $opciones)."'");
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function valor(array $config, string $parametro): ?string
    {
        // Migrations, seeders and queue workers may legitimately run long statements.
        $valor = $this->enConsola && $parametro === 'statement_timeout' ? '0' : ($config[$parametro] ?? null);

        if (! is_string($valor) || $valor === '') {
            return null;
        }

        if (preg_match('/^\d+(ms|s|min|h)?\z/', $valor) !== 1) {
            Log::error('Límite de sesión de PostgreSQL inválido; se usa el valor por defecto.', ['parametro' => $parametro]);

            return null;
        }

        return $valor;
    }
}
