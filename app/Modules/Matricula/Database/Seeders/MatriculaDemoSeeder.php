<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Database\Seeders;

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Enrollments of the demo groups, dated inside each period's enrollment window; the payments are seeded by Pagos.
final class MatriculaDemoSeeder extends Seeder
{
    private const array ESTUDIANTES_POR_GRUPO = ['ING-1-M' => 10, 'ING-5-N' => 6, 'POR-1-T' => 5, 'FRA-1-N' => 4, 'ITA-1-M' => 3, 'QUE-1-S' => 4, 'ING-2-M' => 2];

    public function run(): void
    {
        $personas = Persona::query()->whereNull('user_id')->orderBy('id')->get();
        $desplazamiento = 0;

        Transaccion::ejecutar(function () use ($personas, &$desplazamiento): void {
            Grupo::query()->with('periodo')->orderBy('id')->get()->each(function (Grupo $grupo) use ($personas, &$desplazamiento): void {
                $cantidad = self::ESTUDIANTES_POR_GRUPO[$grupo->codigo] ?? 0;
                $this->matricular($grupo, $personas->slice($desplazamiento % $personas->count(), $cantidad)->values());
                $desplazamiento += $cantidad;
            });
        });

        $this->solicitudesDeClasificacion($personas);
    }

    /**
     * @param  Collection<int, Persona>  $personas
     */
    private function matricular(Grupo $grupo, Collection $personas): void
    {
        $apertura = CarbonImmutable::parse($grupo->periodo->matricula_inicio->toDateString(), 'America/Lima')->setTime(10, 0);

        $personas->each(fn (Persona $persona, int $indice): Matricula => Matricula::factory()->create([
            'persona_id' => $persona->id, 'grupo_id' => $grupo->id,
            'matriculado_en' => $apertura->addDays($indice % 10)->addMinutes($indice * 17),
        ]));

        DB::table('aca_grupos')->where('id', $grupo->id)->increment('matriculados', $personas->count());

        if ($grupo->codigo === 'QUE-1-S' && $personas->isNotEmpty()) {
            $this->retirar(Matricula::query()->where('grupo_id', $grupo->id)->orderByDesc('id')->firstOrFail(), $grupo);
        }
    }

    private function retirar(Matricula $matricula, Grupo $grupo): void
    {
        $matricula->update(['estado' => EstadoMatricula::Retirada, 'retirado_en' => $matricula->matriculado_en->addDays(12)]);
        DB::table('aca_grupos')->where('id', $grupo->id)->decrement('matriculados');
    }

    /**
     * @param  Collection<int, Persona>  $personas
     */
    private function solicitudesDeClasificacion(Collection $personas): void
    {
        $idiomas = Idioma::query()->pluck('id', 'codigo');
        $hoy = CarbonImmutable::today('America/Lima');
        $ultimas = $personas->reverse()->values();

        foreach ([['POR', 0], ['FRA', 1]] as [$codigo, $posicion]) {
            SolicitudClasificacion::factory()->create([
                'persona_id' => $ultimas->get($posicion)?->id, 'idioma_id' => $idiomas[$codigo],
                'vence_en' => $hoy->addDays(25 + $posicion)->toDateString(),
            ]);
        }

        SolicitudClasificacion::factory()->create([
            'persona_id' => $ultimas->get(2)?->id, 'idioma_id' => $idiomas['ING'],
            'estado' => EstadoSolicitudClasificacion::Evaluado, 'vence_en' => $hoy->addDays(20)->toDateString(),
            'fecha_examen' => $hoy->subDays(3)->toDateString(), 'puntaje' => '68.00',
            'ciclo_asignado_id' => Ciclo::query()->where(['idioma_id' => $idiomas['ING'], 'numero' => 5])->value('id'),
            'evaluado_por' => User::query()->where('email', 'admin@ciln.test')->value('id'), 'evaluado_en' => $hoy->subDays(3)->setTime(16, 0),
        ]);
    }
}
