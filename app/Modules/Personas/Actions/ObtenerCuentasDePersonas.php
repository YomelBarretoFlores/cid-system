<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\CuentasDePersonas;
use App\Modules\Personas\Data\CuentaDelPortal;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

final readonly class ObtenerCuentasDePersonas implements CuentasDePersonas
{
    public function __invoke(array $personaIds): array
    {
        $personas = Persona::query()->whereKey($personaIds)->whereNotNull('user_id')->pluck('user_id', 'id');
        $cuentas = User::query()->whereKey($personas->values()->all())->get(['id', 'usuario', 'email', 'desactivado_en'])->keyBy('id');

        return $personas
            ->filter(fn (int $usuarioId): bool => $cuentas->has($usuarioId))
            ->map(fn (int $usuarioId, int $personaId): CuentaDelPortal => ObtenerCuentasDePersonas::cuenta($personaId, $cuentas->get($usuarioId)))
            ->all();
    }

    public static function cuenta(int $personaId, ?User $usuario): CuentaDelPortal
    {
        $usuario ??= new User;

        return new CuentaDelPortal($personaId, (int) $usuario->id, $usuario->usuario, $usuario->email, $usuario->desactivado_en !== null);
    }
}
