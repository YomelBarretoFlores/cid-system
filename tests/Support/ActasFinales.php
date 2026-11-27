<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academico\Database\Factories\ComponenteEvaluacionFactory;
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

// A closed course ready for its final grade sheet: grade calendar 2027-03-29 to 2027-04-05, scheme of one skill (oral) and two exams with passing grade 11: final = (oral + (parcial + final) / 2) / 2, rounded.
final class ActasFinales
{
    /**
     * @return array{grupo: Grupo, usuario: User, docente: Docente, oral: ComponenteEvaluacion, parcial: ComponenteEvaluacion, final: ComponenteEvaluacion}
     */
    public static function curso(): array
    {
        [$usuario, $docente] = PortalDocente::docente();
        Persona::query()->whereKey($docente->persona_id)->update(['apellido_paterno' => 'Vega', 'apellido_materno' => 'Ríos', 'nombres' => 'Carlos']);
        $idioma = self::idioma('Inglés');
        $ciclo = Ciclo::query()->updateOrCreate(['idioma_id' => $idioma->id, 'numero' => 1], ['nivel' => NivelCurricular::Basico, 'nombre' => 'Básico I', 'horas_lectivas' => 60]);
        $periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'actas_inicio' => '2027-03-29', 'actas_fin' => '2027-04-05']);
        $grupo = Grupo::factory()->create(['codigo' => 'ING-1-M', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Cerrado]);
        PortalDocente::asignar($grupo, $docente);

        // One transaction, so the scheme and its components reach the commit-time groups check together (concurrency tests commit).
        return DB::transaction(function () use ($idioma, $grupo, $usuario, $docente): array {
            $esquema = EsquemaEvaluacion::factory()->create(['nombre' => 'Esquema general', 'idioma_id' => $idioma->id, 'nota_maxima' => '20.00', 'nota_minima_aprobatoria' => '11.00', 'vigente_desde' => '2027-01-01']);
            $columnas = [
                'grupo' => $grupo, 'usuario' => $usuario, 'docente' => $docente,
                'oral' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'nombre' => 'Expresión oral', 'orden' => 1]),
                'parcial' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'orden' => 2]),
                'final' => ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'nombre' => 'Examen final', 'orden' => 3]),
            ];
            ComponenteEvaluacionFactory::promediosCiln($esquema, ['oral'], ['parcial', 'final'], 4);

            return $columnas;
        });
    }

    public static function estudiante(Grupo $grupo, string $apellido, ?string $documento = null): Matricula
    {
        $persona = Persona::factory()->create(['apellido_paterno' => $apellido, 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', ...($documento === null ? [] : ['numero_documento' => $documento])]);

        return Matricula::factory()->activa()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id]);
    }

    /**
     * The cycles of a language, numbered in study order, with the given count per level (4/4/4 like the demo data); existing cycles are kept.
     *
     * @param  list<int>  $ciclosPorNivel  cycles of Básico, Intermedio and Avanzado
     */
    public static function planDeEstudios(string $idioma, array $ciclosPorNivel = [4, 4, 4]): Idioma
    {
        $registrado = self::idioma($idioma);
        $numero = 0;

        foreach (NivelCurricular::cases() as $posicion => $nivel) {
            for ($delNivel = 1; $delNivel <= $ciclosPorNivel[$posicion]; $delNivel++) {
                $numero++;
                Ciclo::query()->firstOrCreate(['idioma_id' => $registrado->id, 'numero' => $numero], ['nivel' => $nivel, 'nombre' => "{$nivel->etiqueta()} {$numero}", 'horas_lectivas' => 60]);
            }
        }

        return $registrado;
    }

    /**
     * Closed actas of past cycles for one student, without going through the teacher flow; the language gets the 4/4/4 plan unless it has its cycles already.
     *
     * @param  list<array{0: int, 1: ?string, 2: string, 3?: int, 4?: array{?string, string}}>  $ciclos  cycle number, average (null for NSP), final state and, for an accelerated group, its second cycle number and that cycle's average and state (the first cycle's when left out)
     */
    public static function historial(Persona $persona, string $idioma, array $ciclos, string $estadoActa = 'cerrada', ?int $periodoId = null): void
    {
        $docente = Docente::factory()->create();
        $esquema = DB::transaction(function (): EsquemaEvaluacion {
            $esquema = EsquemaEvaluacion::factory()->create();
            ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'orden' => 1]);
            ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'orden' => 2]);
            ComponenteEvaluacionFactory::promediosCiln($esquema, ['oral'], ['final'], 3);

            return $esquema;
        });
        $usuario = User::factory()->create();
        $idiomaId = self::planDeEstudios($idioma)->id;

        foreach ($ciclos as $fila) {
            [$numero, $promedio, $estado] = $fila;
            $hasta = $fila[3] ?? null;
            $ciclo = Ciclo::query()->where(['idioma_id' => $idiomaId, 'numero' => $numero])->sole();
            $nivel = $ciclo->nivel;
            $grupo = Grupo::factory()->create([
                'ciclo_id' => $ciclo->id, ...($periodoId === null ? [] : ['periodo_id' => $periodoId]),
                ...($hasta === null ? [] : ['es_acelerado' => true, 'ciclo_hasta_id' => Ciclo::query()->where(['idioma_id' => $idiomaId, 'numero' => $hasta])->value('id')]),
            ]);
            $actaId = DB::table('cer_actas')->insertGetId([
                'grupo_id' => $grupo->id, 'periodo_id' => $grupo->periodo_id, 'estado' => 'abierta', 'grupo_codigo' => "G-{$numero}",
                'idioma' => $idioma, 'nivel' => $nivel->value, 'ciclo_numero' => $numero, 'ciclo_nombre' => "{$nivel->etiqueta()} {$numero}",
                'horas_lectivas' => $hasta === null ? 60 : 120, 'periodo' => "Periodo {$numero}", 'docente_id' => $docente->id, 'docente_nombre' => 'Docente',
                ...($hasta === null ? [] : ['ciclo_numero_hasta' => $hasta, 'ciclo_nombre_hasta' => "{$nivel->etiqueta()} {$hasta}", 'horas_lectivas_hasta' => 60]),
                'esquema_id' => $esquema->id, 'esquema_nombre' => 'Esquema', 'nota_maxima' => '20.00', 'nota_minima_aprobatoria' => '11.00',
                'componentes' => '[]', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('cer_notas_finales')->insert([
                'acta_id' => $actaId, 'matricula_id' => Matricula::factory()->create(['persona_id' => $persona->id, 'grupo_id' => $grupo->id])->id,
                'persona_id' => $persona->id, 'estudiante_nombre' => $persona->nombre_completo, 'estudiante_documento' => "DNI {$persona->numero_documento}",
                'notas' => '{}', 'promedio' => $promedio, 'estado_final' => $estado, 'created_at' => now(), 'updated_at' => now(),
                ...($hasta === null ? [] : ['promedio_hasta' => ($fila[4] ?? [$promedio])[0], 'estado_final_hasta' => ($fila[4] ?? [null, $estado])[1]]),
            ]);

            // Validated in the past and in cycle order, so documents issued now come after every acta.
            if ($estadoActa === 'cerrada') {
                DB::table('cer_actas')->where('id', $actaId)->update(['estado' => 'cerrada', 'validada_por' => $usuario->id, 'validada_en' => now()->subMinutes(60 - $numero)]);
            }
        }
    }

    private static function idioma(string $nombre): Idioma
    {
        return Idioma::query()->where('nombre', $nombre)->first() ?? Idioma::factory()->create(['nombre' => $nombre]);
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
