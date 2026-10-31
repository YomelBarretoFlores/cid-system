<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Seeders;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Actions\GuardarSilabo;
use App\Modules\Docencia\Actions\PublicarSilabo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Silabo;
use App\Modules\Docencia\Support\SemanasDelPeriodo;
use Illuminate\Database\Seeder;

// The demo teacher's first current group has a published syllabus and the second a draft; another group of the first one's cycle
// gets a published syllabus too, so "Copiar de un sílabo anterior" offers something. Groups that already have one are left alone.
final class SilabosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $docente = CuentasDeDemo::docenteDemo();
        $grupos = Grupo::query()
            ->whereIn('id', CargaAcademica::query()->where('docente_id', $docente->id ?? 0)->select('grupo_id'))
            ->whereRelation('periodo', 'estado', 'en_curso')->orderBy('codigo')->get();
        $publicado = $grupos->first();
        $creados = $this->escribir($publicado, true)
            + $this->escribir($publicado instanceof Grupo ? $this->otroDelMismoCiclo($publicado) : null, true)
            + $this->escribir($grupos->get(1), false);

        $this->command?->info("Sílabos de demo: {$creados} creados");
    }

    private function escribir(?Grupo $grupo, bool $publicar): int
    {
        $docenteId = $grupo instanceof Grupo ? CargaAcademica::query()->where('grupo_id', $grupo->id)->value('docente_id') : null;

        if (! $grupo instanceof Grupo || $docenteId === null || Silabo::query()->where('grupo_id', $grupo->id)->exists()) {
            return 0;
        }

        $horario = app(HorariosDeGrupos::class)([$grupo->id])[$grupo->id];
        $idioma = mb_strtolower($horario->idioma);
        $semanas = SemanasDelPeriodo::contar($horario->periodoInicio, $horario->periodoFin);

        if (! $publicar) {
            app(GuardarSilabo::class)($grupo->id, (int) $docenteId, SilaboDeDemo::borrador($idioma, $semanas));

            return 1;
        }

        return $this->publicar($horario, (int) $docenteId, SilaboDeDemo::completo($idioma, $semanas, $this->codigos($grupo->id)));
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function publicar(HorarioDeGrupo $grupo, int $docenteId, array $datos): int
    {
        if (app(EsquemaDelGrupo::class)($grupo->grupoId) === null) {
            return 0;
        }

        app(PublicarSilabo::class)($grupo->grupoId, $docenteId, $datos, CuentasDeDemo::usuarioDe($docenteId));

        return 1;
    }

    /**
     * @return list<string>
     */
    private function codigos(int $grupoId): array
    {
        return array_map(fn (ComponenteDeEvaluacion $componente): string => $componente->codigo, app(EsquemaDelGrupo::class)($grupoId)->componentes ?? []);
    }

    // Another teacher's group first, then the latest period; only groups whose syllabus can still be edited.
    private function otroDelMismoCiclo(Grupo $grupo): ?Grupo
    {
        $candidatos = Grupo::query()->with('periodo')
            ->where('ciclo_id', $grupo->ciclo_id)->whereKeyNot($grupo->id)
            ->whereIn('estado', [EstadoGrupo::Programado, EstadoGrupo::Abierto, EstadoGrupo::Cerrado])
            ->whereIn('id', CargaAcademica::query()->select('grupo_id'))->get();
        $docenteId = CargaAcademica::query()->where('grupo_id', $grupo->id)->value('docente_id');
        $deOtros = CargaAcademica::query()->whereIn('grupo_id', $candidatos->modelKeys())->where('docente_id', '!=', $docenteId)->pluck('grupo_id');

        return $candidatos->sortByDesc(fn (Grupo $candidato): string => ($deOtros->contains($candidato->id) ? '1' : '0').$candidato->periodo->fecha_inicio->toDateString())->first();
    }
}
