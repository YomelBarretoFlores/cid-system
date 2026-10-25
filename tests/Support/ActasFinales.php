<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

// A closed course ready for its final grade sheet: grade calendar 2027-03-29 to 2027-04-05, scheme 40/40/20 with passing grade 11.
final class ActasFinales
{
    /**
     * @return array{grupo: Grupo, usuario: User, docente: Docente, parcial: ComponenteEvaluacion, final: ComponenteEvaluacion, participacion: ComponenteEvaluacion}
     */
    public static function curso(): array
    {
        [$usuario, $docente] = PortalDocente::docente();
        Persona::query()->whereKey($docente->persona_id)->update(['apellido_paterno' => 'Vega', 'apellido_materno' => 'Ríos', 'nombres' => 'Carlos']);
        $idioma = Idioma::factory()->create(['nombre' => 'Inglés']);
        $ciclo = Ciclo::factory()->for($idioma)->create(['numero' => 1, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico I', 'horas_lectivas' => 60]);
        $periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'actas_inicio' => '2027-03-29', 'actas_fin' => '2027-04-05']);
        $grupo = Grupo::factory()->create(['codigo' => 'ING-1-M', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Cerrado]);
        PortalDocente::asignar($grupo, $docente);
        $esquema = EsquemaEvaluacion::factory()->create(['nombre' => 'Esquema general', 'idioma_id' => $idioma->id, 'nota_maxima' => '20.00', 'nota_minima_aprobatoria' => '11.00', 'vigente_desde' => '2027-01-01']);

        return [
            'grupo' => $grupo, 'usuario' => $usuario, 'docente' => $docente,
            'parcial' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '40.00', 'orden' => 1]),
            'final' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40.00', 'orden' => 2]),
            'participacion' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'participacion', 'nombre' => 'Participación', 'peso' => '20.00', 'orden' => 3]),
        ];
    }

    public static function estudiante(Grupo $grupo, string $apellido, ?string $documento = null): Matricula
    {
        $persona = Persona::factory()->create(['apellido_paterno' => $apellido, 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', ...($documento === null ? [] : ['numero_documento' => $documento])]);

        return Matricula::factory()->activa()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id]);
    }

    /**
     * @param  array<int, string>  $notas  grade per component id
     */
    public static function preActa(Matricula $matricula, array $notas): void
    {
        foreach ($notas as $componenteId => $nota) {
            NotaParcial::query()->create(['grupo_id' => $matricula->grupo_id, 'matricula_id' => $matricula->id, 'componente_id' => $componenteId, 'nota' => $nota, 'registrado_por' => User::factory()->create()->id]);
        }
    }
}
