<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Facades\Log;

// Session limits travel in the connection string, so every PDO (read, write, reconnected) gets them.
final class ConectorPostgres extends PostgresConnector
{
    // Schema changes and seeding may legitimately run long statements; workers and web requests keep the cap.
    public const array COMANDOS_SIN_LIMITE_DE_SENTENCIA = [
        'migrate', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'db:seed', 'schema:dump',
    ];

    private const array LIMITES_SEGUROS = ['lock_timeout' => '5s', 'statement_timeout' => '30s', 'idle_in_transaction_session_timeout' => '60s'];

    /**
     * @var array<string, true>
     */
    private static array $avisados = [];

    public function __construct(private readonly bool $sinLimiteDeSentencia = false) {}

    /**
     * @param  array<string, mixed>  $config
     */
    protected function getDsn(array $config): string
    {
        $opciones = [];

        foreach (array_keys(self::LIMITES_SEGUROS) as $parametro) {
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
        if ($this->sinLimiteDeSentencia && $parametro === 'statement_timeout') {
            return '0';
        }

        $valor = $config[$parametro] ?? null;

        if (! is_string($valor) || $valor === '') {
            return null;
        }

        if (preg_match('/^\d+(us|ms|s|min|h|d)?\z/', $valor) === 1) {
            return $valor;
        }

        // A typo must not silently remove the protection, so the safe default applies instead.
        if (! isset(self::$avisados[$parametro])) {
            self::$avisados[$parametro] = true;
            Log::error('Límite de sesión de PostgreSQL inválido; se usa el valor seguro por defecto.', ['parametro' => $parametro]);
        }

        return self::LIMITES_SEGUROS[$parametro];
    }
}
