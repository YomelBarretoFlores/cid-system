<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as Consulta;
use Illuminate\Support\Facades\DB;

// The accounts able to manage users, read from the tables and not from the permission cache, so a check never trusts a stale copy.
final class GestoresDeUsuarios
{
    public const string SIN_PERMISO = 'No tienes permiso para gestionar los usuarios.';

    public const string NO_PUEDES_QUITARTELO = 'No puedes quitarte el permiso para gestionar usuarios.';

    public const string DEBE_QUEDAR_UNO = 'Debe quedar al menos una cuenta activa con permiso para gestionar usuarios.';

    // Call inside a transaction: every change of accounts, roles or grants waits on this row, so two of them never remove the last manager together.
    public static function bloquear(): void
    {
        self::permiso()->lockForUpdate()->first();
    }

    public static function autorizar(User $actor): void
    {
        $activo = User::query()->whereKey($actor->id)->whereNull('desactivado_en')->exists();

        throw_unless($activo && $actor->can(PermisoUsuario::Gestionar->value), ReglaDeNegocioIncumplida::class, self::SIN_PERMISO);
    }

    public static function asegurarQueLoConserva(User $actor): void
    {
        throw_unless(self::activos()->whereKey($actor->id)->exists(), ReglaDeNegocioIncumplida::class, self::NO_PUEDES_QUITARTELO);
    }

    public static function asegurarQueQuedeUno(): void
    {
        throw_unless(self::activos()->exists(), ReglaDeNegocioIncumplida::class, self::DEBE_QUEDAR_UNO);
    }

    /**
     * @return Builder<User>
     */
    private static function activos(): Builder
    {
        $tipo = (new User)->getMorphClass();
        $permiso = self::permiso()->select('id');

        return User::query()->whereNull('desactivado_en')->where(fn (Builder $consulta): Builder => $consulta
            ->whereExists(fn (Consulta $directo): Consulta => $directo->from('model_has_permissions')
                ->whereColumn('model_has_permissions.model_id', 'users.id')
                ->where('model_has_permissions.model_type', $tipo)
                ->whereIn('model_has_permissions.permission_id', $permiso))
            ->orWhereExists(fn (Consulta $porRol): Consulta => $porRol->from('model_has_roles')
                ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
                ->whereColumn('model_has_roles.model_id', 'users.id')
                ->where('model_has_roles.model_type', $tipo)
                ->whereIn('role_has_permissions.permission_id', $permiso)));
    }

    private static function permiso(): Consulta
    {
        return DB::table('permissions')->where(['name' => PermisoUsuario::Gestionar->value, 'guard_name' => 'web']);
    }
}
