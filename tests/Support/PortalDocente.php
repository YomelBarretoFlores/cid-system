<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Actions\RegistrarLlamadoDeCierre;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

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

    /**
     * An open group of March and April 2027 with classes on Monday and Wednesday from 18:00 to 20:00 and students with a portal account.
     *
     * @return array{Grupo, list<Matricula>}
     */
    public static function grupoConEstudiantes(Docente $docente, string ...$apellidos): array
    {
        $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
        $grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
        Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
        Horario::factory()->for($grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
        self::asignar($grupo, $docente);

        return [$grupo, array_values(array_map(fn (string $apellido): Matricula => Matricula::factory()->create([
            'grupo_id' => $grupo->id,
            'persona_id' => Persona::factory()->create(['user_id' => User::factory()->create()->id, 'apellido_paterno' => $apellido])->id,
            'matriculado_en' => '2027-02-20 15:00:00+00',
        ]), $apellidos))];
    }

    /**
     * Takes the first call at 18:05 and, when given, the second at 19:50 of the 18:00 class, through the teacher's Actions.
     *
     * @param  array<int, string>  $inicio
     * @param  array<int, string>|null  $cierre
     */
    public static function dictarClase(Grupo $grupo, Docente $docente, User $usuario, array $inicio, ?array $cierre = null, string $fecha = '2027-03-10'): ClaseDictada
    {
        Carbon::setTestNow(CarbonImmutable::parse("{$fecha} 18:05", 'America/Lima'));
        $clase = app(RegistrarAsistencia::class)($grupo->id, $docente->id, $fecha, '18:00', $inicio, $usuario);

        if ($cierre !== null) {
            Carbon::setTestNow(CarbonImmutable::parse("{$fecha} 19:50", 'America/Lima'));
            app(RegistrarLlamadoDeCierre::class)($grupo->id, $docente->id, $fecha, '18:00', $cierre, $usuario);
        }

        return $clase->refresh();
    }
}
