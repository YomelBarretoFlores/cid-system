<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Seeders;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use Illuminate\Database\Seeder;

final class AcademicoDemoSeeder extends Seeder
{
    private const array ROMANOS = ['I', 'II', 'III', 'IV'];

    public function run(): void
    {
        $ingles = $this->idiomaConCiclos('ING', 'Inglés', false);
        $this->idiomaConCiclos('QUE', 'Quechua', true);

        $periodo = Periodo::query()->create([
            'anio' => 2027, 'numero' => 1, 'nombre' => 'Enero 2027',
            'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-29',
            'matricula_inicio' => '2026-12-14', 'matricula_fin' => '2027-01-06',
            'estado' => EstadoPeriodo::MatriculaAbierta,
        ]);

        $ingles->ciclos()->whereIn('numero', [1, 2])->get()->each(
            fn (Ciclo $ciclo) => $this->grupoPresencial($periodo, $ciclo),
        );

        EsquemaEvaluacion::query()->create([
            'nombre' => 'Esquema general (demo)', 'nota_maxima' => '20.00',
            'nota_minima_aprobatoria' => '11.00', 'vigente_desde' => '2027-01-01',
        ])->componentes()->createMany([
            ['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '40.00', 'orden' => 1],
            ['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '40.00', 'orden' => 2],
            ['codigo' => 'participacion', 'nombre' => 'Participación', 'peso' => '20.00', 'orden' => 3],
        ]);
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

    private function grupoPresencial(Periodo $periodo, Ciclo $ciclo): void
    {
        $aula = Aula::query()->create(['codigo' => "A-10{$ciclo->numero}", 'nombre' => "Aula A-10{$ciclo->numero}", 'capacidad' => 30]);

        $grupo = Grupo::query()->create([
            'periodo_id' => $periodo->id, 'ciclo_id' => $ciclo->id, 'aula_id' => $aula->id,
            'codigo' => "ING-{$ciclo->numero}-M", 'modalidad' => Modalidad::Presencial,
            'cupo_maximo' => 25, 'cupo_minimo' => 10, 'estado' => EstadoGrupo::Abierto,
        ]);

        $grupo->horarios()->createMany(collect(range(1, 5))->map(
            fn (int $dia): array => ['dia_semana' => $dia, 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
        )->all());
    }
}
