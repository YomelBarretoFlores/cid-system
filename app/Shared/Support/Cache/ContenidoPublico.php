<?php

declare(strict_types=1);

namespace App\Shared\Support\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// The public home page, kept a few minutes per Lima day so enrollment peaks do not rebuild it on every visit.
final class ContenidoPublico
{
    private const string CLAVE = 'portada-publica';

    private const int SEGUNDOS = 180;

    /**
     * @param  Closure(): array<string, mixed>  $construir
     * @return array<string, mixed>
     */
    public static function recordar(string $dia, Closure $construir): array
    {
        $guardado = Cache::get(self::CLAVE);

        if (is_array($guardado) && ($guardado['dia'] ?? null) === $dia && is_array($guardado['contenido'] ?? null)) {
            return $guardado['contenido'];
        }

        $contenido = $construir();
        Cache::put(self::CLAVE, ['dia' => $dia, 'contenido' => $contenido], self::SEGUNDOS);

        return $contenido;
    }

    // After commit, so a visit during the change cannot store the old content again.
    public static function olvidar(): void
    {
        DB::afterCommit(fn (): bool => Cache::forget(self::CLAVE));
    }
}
