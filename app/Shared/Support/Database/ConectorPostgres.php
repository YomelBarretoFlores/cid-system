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
     * @param  array<int, mixed>  $argv
     */
    public static function liberaLimiteDeSentencia(array $argv): bool
    {
        return in_array(self::comando($argv), self::COMANDOS_SIN_LIMITE_DE_SENTENCIA, true);
    }

    /**
     * @param  array<int, mixed>  $argv
     */
    private static function comando(array $argv): ?string
    {
        // The command follows global options such as --env=production or --env production.
        $saltarValor = false;

        foreach (array_slice($argv, 1) as $argumento) {
            if ($saltarValor || ! is_string($argumento)) {
                $saltarValor = false;

                continue;
            }

            if (! str_starts_with($argumento, '-')) {
                return $argumento;
            }

            $saltarValor = in_array($argumento, ['--env', '-e'], true);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function getDsn(array $config): string
    {
        $limites = [];

        foreach (array_keys(self::LIMITES_SEGUROS) as $parametro) {
            $valor = $this->valor($config, $parametro);

            if ($valor !== null) {
                $limites[$parametro] = $valor;
            }
        }

        // Laravel writes server_options into the DSN; explicit server_options win over the default limits.
        $config['server_options'] = [...$limites, ...(is_array($config['server_options'] ?? null) ? $config['server_options'] : [])];

        return parent::getDsn($config);
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
        if (! isset(self::$avisados["{$parametro}={$valor}"])) {
            self::$avisados["{$parametro}={$valor}"] = true;
            Log::error('Límite de sesión de PostgreSQL inválido; se usa el valor seguro por defecto.', ['parametro' => $parametro]);
        }

        return self::LIMITES_SEGUROS[$parametro];
    }
}
