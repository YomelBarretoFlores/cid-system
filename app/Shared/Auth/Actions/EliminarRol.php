<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\GestoresDeUsuarios;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class EliminarRol
{
    public const string ASIGNADO = 'No puedes eliminar un rol asignado a usuarios. Quítalo primero de sus cuentas.';

    public function __invoke(Role $existente, User $actor): void
    {
        Transaccion::ejecutar(function () use ($existente, $actor): void {
            GestoresDeUsuarios::bloquear();
            GestoresDeUsuarios::autorizar($actor);
            $rol = Role::query()->whereKey($existente->getKey())->lockForUpdate()->firstOrFail();

            throw_if(DB::table('model_has_roles')->where('role_id', $rol->id)->exists(), ReglaDeNegocioIncumplida::class, self::ASIGNADO);

            $rol->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
