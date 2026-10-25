<?php

declare(strict_types=1);

namespace App\Shared\Support\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

// The public home page, kept a few minutes per Lima day so enrollment peaks do not rebuild it on every visit.
final class ContenidoPublico
{
    private const string CLAVE = 'portada-publica';

    private const string VERSION = 'portada-publica:version';

    private const int SEGUNDOS = 180;

    /**
     * A failing cache store never takes the page down: it is then built directly.
     *
     * @param  Closure(): array<string, mixed>  $construir
     * @return array<string, mixed>
     */
    public static function recordar(string $dia, Closure $construir): array
    {
        $vigilado = function () use ($construir): array {
            try {
                return $construir();
            } catch (Throwable $error) {
                throw new FalloAlConstruirContenido($error);
            }
        };

        try {
            return self::desdeLaCache($dia, $vigilado);
        } catch (FalloAlConstruirContenido $fallo) {
            throw $fallo->getPrevious() ?? $fallo;
        } catch (Throwable $error) {
            self::avisar($error);

            return $construir();
        }
    }

    // A new version after commit makes any content built before the change unusable, even if a slower visit stores it later.
    public static function olvidar(): void
    {
        DB::afterCommit(function (): void {
            try {
                Cache::forever(self::VERSION, (string) Str::uuid());
            } catch (Throwable $error) {
                self::avisar($error);
            }
        });
    }

    /**
     * @param  Closure(): array<string, mixed>  $construir
     * @return array<string, mixed>
     */
    private static function desdeLaCache(string $dia, Closure $construir): array
    {
        $guardado = self::vigente($dia, self::version());

        if ($guardado !== null) {
            return $guardado;
        }

        // One visit rebuilds while the others wait for it, instead of all rebuilding at once during a peak.
        return Cache::lock(self::CLAVE.':armando', 15)->block(5, function () use ($dia, $construir): array {
            $version = self::version();

            return self::vigente($dia, $version) ?? self::guardar($dia, $version, $construir());
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function vigente(string $dia, string $version): ?array
    {
        $guardado = Cache::get(self::CLAVE);

        return is_array($guardado) && ($guardado['dia'] ?? null) === $dia && ($guardado['version'] ?? null) === $version && is_array($guardado['contenido'] ?? null)
            ? $guardado['contenido']
            : null;
    }

    /**
     * @param  array<string, mixed>  $contenido
     * @return array<string, mixed>
     */
    private static function guardar(string $dia, string $version, array $contenido): array
    {
        Cache::put(self::CLAVE, ['dia' => $dia, 'version' => $version, 'contenido' => $contenido], self::SEGUNDOS);

        return $contenido;
    }

    private static function version(): string
    {
        $version = Cache::get(self::VERSION);

        return is_string($version) ? $version : '';
    }

    private static function avisar(Throwable $error): void
    {
        Log::warning('No se pudo usar la caché de la portada pública.', ['error' => $error::class]);
    }
}
