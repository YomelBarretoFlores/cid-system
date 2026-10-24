<?php

declare(strict_types=1);

namespace App\Shared\Support\Database;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Closure;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class Transaccion
{
    public const int INTENTOS = 3;

    private const array CONFLICTOS = ['40P01', '40001'];

    private const string BLOQUEO_AGOTADO = '55P03';

    private const string SENTENCIA_CANCELADA = '57014';

    /**
     * @template T
     *
     * @param  Closure(): T  $operacion
     * @param  bool  $reintentar  false when the operation changes a model loaded before the transaction: a retry would see it clean and save nothing
     * @return T
     */
    public static function ejecutar(Closure $operacion, bool $reintentar = true): mixed
    {
        // Only the outermost transaction can safely retry: an inner one shares its caller's snapshot and locks.
        $esExterna = DB::transactionLevel() === 0;
        $intentos = $reintentar ? self::INTENTOS : 1;

        for ($intento = 1; ; $intento++) {
            try {
                return DB::transaction($operacion);
            } catch (QueryException|DeadlockException $error) {
                self::esperarAntesDeReintentar($error, $esExterna, $intento, $intentos);
            }
        }
    }

    private static function esperarAntesDeReintentar(QueryException|DeadlockException $error, bool $esExterna, int $intento, int $intentos): void
    {
        $sqlstate = self::sqlstate($error);
        $esConflicto = in_array($sqlstate, self::CONFLICTOS, true);

        if ($esConflicto && ! $esExterna) {
            throw $error;
        }

        if (! $esConflicto || $intento >= $intentos) {
            throw self::traducir($error, $sqlstate);
        }

        Log::warning('Transacción reintentada tras un conflicto de concurrencia.', ['sqlstate' => $sqlstate, 'intento' => $intento]);
        usleep(random_int(20, 80) * 1000 * $intento);
    }

    // Laravel rethrows a deadlock from a nested transaction as DeadlockException wrapping the original QueryException.
    private static function sqlstate(QueryException|DeadlockException $error): string
    {
        $origen = $error instanceof DeadlockException ? $error->getPrevious() : $error;

        return $origen instanceof QueryException ? (string) $origen->getCode() : '40P01';
    }

    private static function traducir(QueryException|DeadlockException $error, string $sqlstate): \Throwable
    {
        if ($sqlstate === self::BLOQUEO_AGOTADO || in_array($sqlstate, self::CONFLICTOS, true)) {
            Log::warning('Operación rechazada por un conflicto de concurrencia.', ['sqlstate' => $sqlstate]);

            return new ReglaDeNegocioIncumplida('Otra operación está modificando estos datos en este momento. Inténtalo de nuevo en unos segundos.', previous: $error);
        }

        if ($sqlstate === self::SENTENCIA_CANCELADA) {
            Log::warning('Operación cancelada por tiempo de ejecución.', ['sqlstate' => $sqlstate]);

            return new ReglaDeNegocioIncumplida('La operación tardó demasiado y se canceló. Inténtalo de nuevo; si se repite, avisa a soporte.', previous: $error);
        }

        return $error;
    }
}
