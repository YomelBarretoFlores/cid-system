<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;

final class OfertaAcademica
{
    /** A group with a Monday class and an assigned teacher, ready to open. */
    public static function prepararParaAbrir(Grupo $grupo): Grupo
    {
        Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
        self::asignarDocente($grupo);

        return $grupo;
    }

    public static function asignarDocente(Grupo $grupo): CargaAcademica
    {
        return CargaAcademica::query()->create(['grupo_id' => $grupo->id, 'docente_id' => Docente::factory()->create()->id, 'asignado_por' => User::factory()->create()->id]);
    }

    public static function registrarClase(Grupo $grupo): ClaseDictada
    {
        $carga = CargaAcademica::query()->where('grupo_id', $grupo->id)->first() ?? self::asignarDocente($grupo);

        return ClaseDictada::query()->create([
            'grupo_id' => $grupo->id, 'fecha' => '2027-03-01', 'hora_inicio' => '18:00', 'hora_fin' => '20:00',
            'docente_id' => $carga->docente_id, 'registrado_por' => User::factory()->create()->id,
        ]);
    }

    public static function matricular(Grupo $grupo, int $estudiantes): Grupo
    {
        DB::table('aca_grupos')->where('id', $grupo->id)->update(['matriculados' => $estudiantes]);

        return $grupo->refresh();
    }
}
