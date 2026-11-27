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
        $resultado = [];

        foreach (Persona::query()->whereKey($personaIds)->whereNotNull('user_id')->with('user:id,usuario,email,desactivado_en')->get() as $persona) {
            if ($persona->user instanceof User) {
                $resultado[$persona->id] = self::cuenta($persona->id, $persona->user);
            }
        }

        return $resultado;
    }

    public static function cuenta(int $personaId, User $usuario): CuentaDelPortal
    {
        return new CuentaDelPortal($personaId, $usuario->id, $usuario->usuario, $usuario->email, $usuario->desactivado_en !== null);
    }
}
