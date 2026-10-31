<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

final class CuentasDeDemo
{
    public static function personaDelEstudiante(): ?int
    {
        $id = Persona::query()->whereRelation('user', 'email', 'estudiante@ciln.test')->value('id');

        return $id === null ? null : (int) $id;
    }

    public static function docenteDemo(): ?Docente
    {
        return Docente::query()->whereIn('persona_id', Persona::query()->whereRelation('user', 'email', 'docente@ciln.test')->select('id'))->first();
    }

    // A teacher with an account signs in as themself; the synthetic teachers without one are recorded by the administrator.
    public static function usuarioDe(int $docenteId): User
    {
        $usuarioId = Persona::query()->whereIn('id', Docente::query()->whereKey($docenteId)->select('persona_id'))->value('user_id');

        return User::query()->whereKey($usuarioId)->first() ?? User::query()->where('email', 'admin@ciln.test')->firstOrFail();
    }
}
