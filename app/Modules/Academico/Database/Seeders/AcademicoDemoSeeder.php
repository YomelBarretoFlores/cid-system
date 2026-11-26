<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Seeders;

use App\Modules\Academico\Actions\GuardarEsquemaDeEvaluacion;
use App\Modules\Academico\Data\DatosDeEsquema;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\RegimenDePrograma;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

// Dates are relative to today in Lima: the previous four months are closed with their final grade sheets, the current one is in progress and the next one is open for enrollment.
final class AcademicoDemoSeeder extends Seeder
{
    private const array ROMANOS = ['I', 'II', 'III', 'IV'];

    private const array MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    private const array IDIOMAS = [['ING', 'Inglés', false], ['POR', 'Portugués', false], ['FRA', 'Francés', false], ['ITA', 'Italiano', false], ['QUE', 'Quechua', true]];

    private const array AULAS = [['A-102', 'Pab. Central'], ['A-103', 'Pab. Central'], ['A-104', 'Pab. Central'], ['A-204', 'Pab. Central'], ['A-301', 'Pab. Central'], ['C-105', 'Pab. Letras']];

    /**
     * Code, language, cycle, classroom and sessions (days, start, end) of each demo group.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: string, 4: list<int>, 5: string, 6: string}>
     */
    private const array GRUPOS_EN_CURSO = [
        ['ING-1-M', 'ING', 1, 'A-204', [1, 3, 5], '07:00', '08:30'],
        ['ING-5-N', 'ING', 5, 'A-301', [1, 3], '18:00', '20:15'],
        ['POR-1-T', 'POR', 1, 'A-102', [2, 4], '16:00', '18:00'],
        ['FRA-1-N', 'FRA', 1, 'A-103', [2, 4], '18:30', '20:30'],
        ['ITA-1-M', 'ITA', 1, 'A-104', [2, 4], '07:00', '08:30'],
        ['QUE-1-S', 'QUE', 1, 'C-105', [6], '14:00', '18:30'],
    ];

    /**
     * @var list<array{0: string, 1: string, 2: int, 3: string, 4: list<int>, 5: string, 6: string}>
     */
    private const array GRUPOS_SIGUIENTES = [
        ['ING-1-M', 'ING', 1, 'A-204', [1, 3, 5], '07:00', '08:30'],
        ['ING-2-M', 'ING', 2, 'A-301', [1, 3, 5], '07:00', '08:30'],
        ['POR-2-T', 'POR', 2, 'A-102', [2, 4], '16:00', '18:00'],
    ];

    /**
     * @var list<array{0: string, 1: string, 2: int, 3: string, 4: list<int>, 5: string, 6: string}>
     */
    private const array GRUPOS_ANTERIORES = [
        ['ING-1-M', 'ING', 1, 'A-204', [1, 3, 5], '07:00', '08:30'],
        ['POR-1-T', 'POR', 1, 'A-102', [2, 4], '16:00', '18:00'],
        ['QUE-1-S', 'QUE', 1, 'C-105', [6], '14:00', '18:30'],
    ];

    /**
     * One English cohort that took cycles I to IV in the four previous months, so the demo can issue a level certificate.
     *
     * @var list<array{0: string, 1: string, 2: int, 3: string, 4: list<int>, 5: string, 6: string}>
     */
    private const array COHORTE = [
        ['ING-1-A', 'ING', 1, 'A-103', [1, 3, 5], '09:00', '10:30'],
        ['ING-2-A', 'ING', 2, 'A-103', [1, 3, 5], '09:00', '10:30'],
        ['ING-3-A', 'ING', 3, 'A-103', [1, 3, 5], '09:00', '10:30'],
        ['ING-4-A', 'ING', 4, 'A-103', [1, 3, 5], '09:00', '10:30'],
    ];

    /**
     * Groups outside Jóvenes y Adultos; Posgrado only admits accelerated groups.
     *
     * @var array<string, string>
     */
    private const array PROGRAMAS = ['ING-2-M' => 'NINOS', 'POR-2-T' => 'POSGRADO'];

    public function run(): void
    {
        $idiomas = [];
        foreach (self::IDIOMAS as [$codigo, $nombre, $esLenguaNativa]) {
            $idiomas[$codigo] = $this->idiomaConCiclos($codigo, $nombre, $esLenguaNativa);
        }

        $aulas = collect(self::AULAS)->mapWithKeys(fn (array $aula): array => [
            $aula[0] => Aula::query()->create(['codigo' => $aula[0], 'nombre' => $aula[1], 'ubicacion' => $aula[1], 'capacidad' => 30])->id,
        ])->all();

        $hoy = CarbonImmutable::today('America/Lima');
        $actual = $this->periodo($hoy->startOfMonth(), $hoy->startOfMonth()->subDays(14), EstadoPeriodo::EnCurso);
        $siguiente = $this->periodo($hoy->startOfMonth()->addMonth(), min($hoy, $hoy->startOfMonth()->addMonth()->subDays(10)), EstadoPeriodo::MatriculaAbierta);

        $anterior = $this->periodo($hoy->startOfMonth()->subMonth(), $hoy->startOfMonth()->subMonth()->subDays(14), EstadoPeriodo::Cerrado);

        $this->grupos($actual, self::GRUPOS_EN_CURSO, $idiomas, $aulas);
        $this->grupos($siguiente, self::GRUPOS_SIGUIENTES, $idiomas, $aulas);
        // Created last so the first demo groups stay those of the current period.
        $this->grupos($anterior, self::GRUPOS_ANTERIORES, $idiomas, $aulas, EstadoGrupo::Cerrado);

        foreach ([4, 3, 2] as $posicion => $mesesAtras) {
            $inicio = $hoy->startOfMonth()->subMonths($mesesAtras);
            $this->grupos($this->periodo($inicio, $inicio->subDays(14), EstadoPeriodo::Cerrado), [self::COHORTE[$posicion]], $idiomas, $aulas, EstadoGrupo::Cerrado);
        }

        $this->grupos($anterior, [self::COHORTE[3]], $idiomas, $aulas, EstadoGrupo::Cerrado);

        app(GuardarEsquemaDeEvaluacion::class)(new DatosDeEsquema(
            nombre: 'Esquema general (demo)', idiomaId: null, nivel: null, notaMaxima: '20', notaMinimaAprobatoria: '11',
            vigenteDesde: $hoy->subMonths(6)->startOfYear(), vigenteHasta: null,
            componentes: [
                ['codigo' => 'auditiva', 'nombre' => 'Comprensión auditiva', 'grupo' => 'habilidades'],
                ['codigo' => 'oral', 'nombre' => 'Expresión oral', 'grupo' => 'habilidades'],
                ['codigo' => 'lectora', 'nombre' => 'Comprensión lectora', 'grupo' => 'habilidades'],
                ['codigo' => 'escrita', 'nombre' => 'Expresión escrita', 'grupo' => 'habilidades'],
                ['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'grupo' => 'examenes'],
                ['codigo' => 'final', 'nombre' => 'Examen final', 'grupo' => 'examenes'],
            ],
        ));
    }

    private function idiomaConCiclos(string $codigo, string $nombre, bool $esLenguaNativa): Idioma
    {
        $idioma = Idioma::query()->create(['codigo' => $codigo, 'nombre' => $nombre, 'es_lengua_nativa' => $esLenguaNativa]);

        foreach (NivelCurricular::cases() as $posicionNivel => $nivel) {
            foreach (self::ROMANOS as $posicion => $romano) {
                $idioma->ciclos()->create([
                    'numero' => $posicionNivel * 4 + $posicion + 1,
                    'nivel' => $nivel,
                    'nombre' => "{$nivel->etiqueta()} {$romano}",
                    'horas_lectivas' => 60,
                ]);
            }
        }

        return $idioma;
    }

    private function periodo(CarbonImmutable $inicio, CarbonImmutable $aperturaDeMatricula, EstadoPeriodo $estado): Periodo
    {
        return Periodo::query()->create([
            'anio' => $inicio->year, 'numero' => $inicio->month, 'nombre' => self::MESES[$inicio->month - 1].' '.$inicio->year,
            'fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $inicio->endOfMonth()->toDateString(),
            'matricula_inicio' => $aperturaDeMatricula->toDateString(), 'matricula_fin' => $inicio->addDays(4)->toDateString(),
            ...$this->calendarioDeActas($inicio->endOfMonth()->startOfDay()),
            'estado' => $estado,
        ]);
    }

    /**
     * Final grade sheets are validated from five days before the end of classes to five days after; the previous month's
     * calendar stays open until today so its demo actas can be closed through the real Action.
     *
     * @return array{actas_inicio: string, actas_fin: string}
     */
    private function calendarioDeActas(CarbonImmutable $fin): array
    {
        return ['actas_inicio' => $fin->subDays(5)->toDateString(), 'actas_fin' => max($fin->addDays(5), CarbonImmutable::today('America/Lima'))->toDateString()];
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int, 3: string, 4: list<int>, 5: string, 6: string}>  $grupos
     * @param  array<string, Idioma>  $idiomas
     * @param  array<string, int>  $aulas
     */
    private function grupos(Periodo $periodo, array $grupos, array $idiomas, array $aulas, EstadoGrupo $estado = EstadoGrupo::Abierto): void
    {
        foreach ($grupos as [$codigo, $idioma, $ciclo, $aula, $dias, $inicio, $fin]) {
            $programa = Programa::query()->where('codigo', self::PROGRAMAS[$codigo] ?? 'JA')->firstOrFail();
            $esAcelerado = $programa->regimen === RegimenDePrograma::Acelerado;
            $grupo = Grupo::query()->create([
                'programa_id' => $programa->id, 'es_acelerado' => $esAcelerado,
                'periodo_id' => $periodo->id, 'ciclo_id' => Ciclo::query()->where(['idioma_id' => $idiomas[$idioma]->id, 'numero' => $ciclo])->value('id'),
                // An accelerated group also covers the next cycle of its level.
                'ciclo_hasta_id' => $esAcelerado ? Ciclo::query()->where(['idioma_id' => $idiomas[$idioma]->id, 'numero' => $ciclo + 1])->value('id') : null,
                'aula_id' => $aulas[$aula], 'codigo' => $codigo, 'modalidad' => Modalidad::Presencial,
                'cupo_maximo' => 25, 'cupo_minimo' => 5, 'estado' => $estado,
            ]);
            $grupo->horarios()->createMany(array_map(fn (int $dia): array => ['dia_semana' => $dia, 'hora_inicio' => $inicio, 'hora_fin' => $fin], $dias));
        }
    }
}
