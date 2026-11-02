<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Usuarios;

use App\Shared\Auth\Data\DatosDeUsuarioDelPersonal;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\CatalogoDePermisos;

final class FormularioDeUsuario
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data, ?string $correo): DatosDeUsuarioDelPersonal
    {
        $roles = is_array($data['roles'] ?? null) ? $data['roles'] : [];

        return new DatosDeUsuarioDelPersonal(
            nombre: is_string($data['name'] ?? null) ? $data['name'] : '',
            correo: $correo,
            roles: array_values(array_map(intval(...), array_filter($roles, is_numeric(...)))),
            permisos: CatalogoDePermisos::aplanar($data['permisos'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function estado(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'roles' => $usuario->roles->pluck('id')->all(),
            'permisos' => CatalogoDePermisos::agrupar($usuario->getDirectPermissions()->pluck('name')),
        ];
    }
}
