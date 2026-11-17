<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Certificacion\Contracts\EstadosDeActas;
use App\Modules\Docencia\Contracts\NotasParcialesDelGrupo;
use App\Modules\Docencia\Data\ClaseProgramada;
use App\Modules\Docencia\Data\ResumenDeMiGrupo;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;

// The cards of "Mis grupos": per group, the classes held over the programmed ones, the grades recorded and the acta.
final readonly class ResumirMisGrupos
{
    public function __construct(
        private ListarGruposDelDocente $listarGrupos,
        private EstadosDeActas $estadosDeActas,
        private NotasParcialesDelGrupo $notasParciales,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
        private EsquemaDelGrupo $esquemaDelGrupo,
        private CursoDelGrupo $cursoDelGrupo,
    ) {}

    /**
     * @return list<ResumenDeMiGrupo>
     */
    public function __invoke(int $docenteId): array
    {
        $grupos = ($this->listarGrupos)($docenteId);
        $ids = array_map(fn (HorarioDeGrupo $grupo): int => $grupo->grupoId, $grupos);
        $actas = ($this->estadosDeActas)($ids);
        $dictadas = ClaseDictada::query()->whereIn('grupo_id', $ids)->groupBy('grupo_id')->selectRaw('grupo_id, count(*) as total')->pluck('total', 'grupo_id');

        return array_map(function (HorarioDeGrupo $grupo) use ($actas, $dictadas): ResumenDeMiGrupo {
            $programadas = ClasesDelHorario::delPeriodo($grupo);
            $hoy = ClasesDelHorario::hoy();
            $proxima = collect($programadas)->first(fn (ClaseProgramada $clase): bool => $clase->fecha->gte($hoy));
            [$registradas, $esperadas] = $this->notas($grupo->grupoId);
            $curso = ($this->cursoDelGrupo)($grupo->grupoId);

            return new ResumenDeMiGrupo(
                grupo: $grupo,
                dictadas: (int) ($dictadas[$grupo->grupoId] ?? 0),
                programadas: count($programadas),
                proxima: $proxima,
                esHoy: $proxima instanceof ClaseProgramada && $proxima->fecha->isSameDay($hoy),
                notasRegistradas: $registradas,
                notasEsperadas: $esperadas,
                acta: $actas[$grupo->grupoId] ?? null,
                actasInicio: $curso?->actasInicio,
                actasFin: $curso?->actasFin,
            );
        }, $grupos);
    }

    /**
     * @return array{int, int} grades recorded for the active students and grades the scheme expects from them
     */
    private function notas(int $grupoId): array
    {
        $matriculas = array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->matriculaId, ($this->estudiantesDelGrupo)($grupoId));
        $componentes = count(($this->esquemaDelGrupo)($grupoId)->componentes ?? []);
        $notas = ($this->notasParciales)($grupoId);
        $registradas = array_sum(array_map(fn (int $matriculaId): int => count($notas[$matriculaId] ?? []), $matriculas));

        return [$registradas, count($matriculas) * $componentes];
    }
}
