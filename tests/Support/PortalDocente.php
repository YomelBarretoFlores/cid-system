<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

final class PortalDocente
{
    /**
     * @return array{User, Docente}
     */
    public static function docente(): array
    {
        $usuario = User::factory()->create();
        $docente = Docente::factory()->create(['persona_id' => Persona::factory()->create(['user_id' => $usuario->id])->id]);

        return [$usuario, $docente];
    }

    public static function asignar(Grupo $grupo, Docente $docente): CargaAcademica
    {
        return CargaAcademica::query()->updateOrCreate(['grupo_id' => $grupo->id], ['docente_id' => $docente->id, 'asignado_por' => User::factory()->create()->id]);
    }
}
