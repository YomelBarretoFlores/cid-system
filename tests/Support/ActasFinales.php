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
use Illuminate\Support\Facades\DB;

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

        // One transaction, so the scheme and its components reach the commit-time weights check together (concurrency tests commit).
        return DB::transaction(function () use ($idioma, $grupo, $usuario, $docente): array {
            $esquema = EsquemaEvaluacion::factory()->create(['nombre' => 'Esquema general', 'idioma_id' => $idioma->id, 'nota_maxima' => '20.00', 'nota_minima_aprobatoria' => '11.00', 'vigente_desde' => '2027-01-01']);

            return [
                'grupo' => $grupo, 'usuario' => $usuario, 'docente' => $docente,
                'parcial' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '40.00', 'orden' => 1]),
                'final' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40.00', 'orden' => 2]),
                'participacion' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'participacion', 'nombre' => 'Participación', 'peso' => '20.00', 'orden' => 3]),
            ];
        });
    }

    public static function estudiante(Grupo $grupo, string $apellido, ?string $documento = null): Matricula
    {
        $persona = Persona::factory()->create(['apellido_paterno' => $apellido, 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', ...($documento === null ? [] : ['numero_documento' => $documento])]);

        return Matricula::factory()->activa()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id]);
    }

    /**
     * Closed actas of past cycles for one student, without going through the teacher flow.
     *
     * @param  list<array{0: int, 1: ?string, 2: string}>  $ciclos  cycle number, average (null for NSP) and final state
     */
    public static function historial(Persona $persona, string $idioma, array $ciclos, string $estadoActa = 'cerrada', ?int $periodoId = null): void
    {
        $docente = Docente::factory()->create();
        $esquema = DB::transaction(function (): EsquemaEvaluacion {
            $esquema = EsquemaEvaluacion::factory()->create();
            ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'peso' => '100.00', 'orden' => 1]);

            return $esquema;
        });
        $usuario = User::factory()->create();
        $idiomaId = Idioma::factory()->create()->id;

        foreach ($ciclos as [$numero, $promedio, $estado]) {
            $nivel = NivelCurricular::cases()[intdiv($numero - 1, 4)];
            $ciclo = Ciclo::query()->firstOrCreate(['idioma_id' => $idiomaId, 'numero' => $numero], ['nivel' => $nivel, 'nombre' => "{$nivel->etiqueta()} {$numero}", 'horas_lectivas' => 60]);
            $grupo = Grupo::factory()->create(['ciclo_id' => $ciclo->id, ...($periodoId === null ? [] : ['periodo_id' => $periodoId])]);
            $actaId = DB::table('cer_actas')->insertGetId([
                'grupo_id' => $grupo->id, 'periodo_id' => $grupo->periodo_id, 'estado' => 'abierta', 'grupo_codigo' => "G-{$numero}",
                'idioma' => $idioma, 'nivel' => $nivel->value, 'ciclo_numero' => $numero, 'ciclo_nombre' => "{$nivel->etiqueta()} {$numero}",
                'horas_lectivas' => 60, 'periodo' => "Periodo {$numero}", 'docente_id' => $docente->id, 'docente_nombre' => 'Docente',
                'esquema_id' => $esquema->id, 'esquema_nombre' => 'Esquema', 'nota_maxima' => '20.00', 'nota_minima_aprobatoria' => '11.00',
                'componentes' => '[]', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('cer_notas_finales')->insert([
                'acta_id' => $actaId, 'matricula_id' => Matricula::factory()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id])->id,
                'persona_id' => $persona->id, 'estudiante_nombre' => $persona->nombre_completo, 'estudiante_documento' => "DNI {$persona->numero_documento}",
                'notas' => '{}', 'promedio' => $promedio, 'estado_final' => $estado, 'created_at' => now(), 'updated_at' => now(),
            ]);

            // Validated in the past and in cycle order, so documents issued now come after every acta.
            if ($estadoActa === 'cerrada') {
                DB::table('cer_actas')->where('id', $actaId)->update(['estado' => 'cerrada', 'validada_por' => $usuario->id, 'validada_en' => now()->subMinutes(60 - $numero)]);
            }
        }
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
