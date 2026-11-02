<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Data\DatosDeRol;
use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\GestoresDeUsuarios;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class GuardarRol
{
    public const string NOMBRE_REPETIDO = 'Ya existe un rol con ese nombre.';

    public function __invoke(DatosDeRol $datos, User $actor, ?Role $existente = null): Role
    {
        try {
            $rol = Transaccion::ejecutar(fn (): Role => $this->guardar($datos, $actor, $existente));
        } catch (UniqueConstraintViolationException) {
            throw new ReglaDeNegocioIncumplida(self::NOMBRE_REPETIDO);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $rol;
    }

    private function guardar(DatosDeRol $datos, User $actor, ?Role $existente): Role
    {
        GestoresDeUsuarios::bloquear();
        GestoresDeUsuarios::autorizar($actor);

        $rol = $existente instanceof Role ? Role::query()->whereKey($existente->getKey())->lockForUpdate()->firstOrFail() : new Role;
        $rol->forceFill(['name' => trim($datos->nombre), 'guard_name' => 'web'])->save();
        // Panel access is not offered on this screen, so a role that already carries it keeps it.
        $conservados = $existente instanceof Role && $rol->hasPermissionTo(User::ADMIN_PANEL_PERMISSION) ? [User::ADMIN_PANEL_PERMISSION] : [];
        $rol->syncPermissions([...array_diff($datos->permisos, [User::ADMIN_PANEL_PERMISSION]), ...$conservados]);

        if (DB::table('model_has_roles')->where(['role_id' => $rol->id, 'model_type' => $actor->getMorphClass(), 'model_id' => $actor->id])->exists()) {
            GestoresDeUsuarios::asegurarQueLoConserva($actor);
        }

        GestoresDeUsuarios::asegurarQueQuedeUno();

        return $rol;
    }
}
