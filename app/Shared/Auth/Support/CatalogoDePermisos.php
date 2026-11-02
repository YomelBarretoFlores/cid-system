<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Shared\Auth\Models\User;
use Illuminate\Support\Str;

// Spanish labels of lang/es/permisos.php, grouped by the module prefix of each permission name.
final class CatalogoDePermisos
{
    public static function etiqueta(string $permiso): ?string
    {
        return self::catalogo()[Str::before($permiso, '.')]['permisos'][$permiso] ?? null;
    }

    /**
     * Panel access is granted with every staff account, so it is never offered.
     *
     * @return array<string, array{nombre: string, permisos: array<string, string>}>
     */
    public static function asignables(): array
    {
        $grupos = self::catalogo();
        unset($grupos[Str::before(User::ADMIN_PANEL_PERMISSION, '.')]['permisos'][User::ADMIN_PANEL_PERMISSION]);

        return array_filter($grupos, fn (array $grupo): bool => $grupo['permisos'] !== []);
    }

    /**
     * @param  iterable<string>  $permisos
     * @return array<string, list<string>>
     */
    public static function agrupar(iterable $permisos): array
    {
        $estado = array_fill_keys(array_keys(self::asignables()), []);

        foreach ($permisos as $permiso) {
            $grupo = Str::before($permiso, '.');

            if (array_key_exists($grupo, $estado) && $permiso !== User::ADMIN_PANEL_PERMISSION) {
                $estado[$grupo][] = $permiso;
            }
        }

        return $estado;
    }

    /**
     * @return list<string>
     */
    public static function aplanar(mixed $estado): array
    {
        if (! is_array($estado)) {
            return [];
        }

        $permisos = [];
        array_walk_recursive($estado, function (mixed $permiso) use (&$permisos): void {
            if (is_string($permiso)) {
                $permisos[] = $permiso;
            }
        });

        return array_values(array_unique($permisos));
    }

    /**
     * @return array<string, array{nombre: string, permisos: array<string, string>}>
     */
    private static function catalogo(): array
    {
        /** @var array<string, array{nombre: string, permisos: array<string, string>}>|string $catalogo */
        $catalogo = trans('permisos', [], 'es');

        return is_array($catalogo) ? $catalogo : [];
    }
}
