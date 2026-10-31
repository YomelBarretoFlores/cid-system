<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Personas\Contracts\FichasDePersonas;
use Illuminate\Support\Collection;

// Codes and names the coordination's class control reads from other modules, resolved in batches.
final readonly class NombresDelControlDeClases
{
    public function __construct(
        private HorariosDeGrupos $horariosDeGrupos,
        private FichasDePersonas $fichasDePersonas,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
        private DocumentoDeMatriculaPorId $documentoDeMatriculaPorId,
    ) {}

    /**
     * @param  list<int>|null  $grupoIds  null: every group with registered classes
     * @return array<int, string> group id => "code · period"
     */
    public function grupos(?array $grupoIds = null): array
    {
        return array_map(fn (HorarioDeGrupo $grupo): string => "{$grupo->codigo} · {$grupo->periodo}", $this->horarios($grupoIds));
    }

    /**
     * @param  list<int>  $grupoIds
     * @return array<int, string> group id => code
     */
    public function codigos(array $grupoIds): array
    {
        return array_map(fn (HorarioDeGrupo $grupo): string => $grupo->codigo, $this->horarios($grupoIds));
    }

    /**
     * @return list<int> groups of the period that have registered classes
     */
    public function gruposDelPeriodo(int $periodoId): array
    {
        return array_values(array_map(
            fn (HorarioDeGrupo $grupo): int => $grupo->grupoId,
            array_filter($this->horarios(null), fn (HorarioDeGrupo $grupo): bool => $grupo->periodoId === $periodoId),
        ));
    }

    /**
     * @param  list<int>|null  $docenteIds  null: every teacher with registered classes
     * @return array<int, string> teacher id => full name, in alphabetical order
     */
    public function docentes(?array $docenteIds = null): array
    {
        $ids = $docenteIds ?? ClaseDictada::query()->distinct()->pluck('docente_id')->map(intval(...))->all();
        $personas = Docente::query()->whereKey($ids)->pluck('persona_id', 'id')->map(intval(...));
        $fichas = ($this->fichasDePersonas)(array_values($personas->unique()->all()));

        return $personas->map(fn (int $personaId): string => $fichas[$personaId]->nombreCompleto ?? '—')->sort()->all();
    }

    /**
     * The delegate who reviewed each class, looked up among the active students of its group,
     * or by the enrollment when it is no longer active.
     *
     * @param  Collection<int, ClaseDictada>  $clases
     * @return array<int, string> enrollment id => full name
     */
    public function delegados(Collection $clases): array
    {
        $revisadas = $clases->whereNotNull('confirmada_por_matricula_id');
        $nombres = [];

        foreach ($revisadas->pluck('grupo_id')->unique() as $grupoId) {
            foreach (($this->estudiantesDelGrupo)((int) $grupoId) as $estudiante) {
                $nombres[$estudiante->matriculaId] = $estudiante->nombreCompleto;
            }
        }

        foreach ($revisadas->pluck('confirmada_por_matricula_id')->map(intval(...))->unique() as $matriculaId) {
            $nombres[$matriculaId] ??= ($this->documentoDeMatriculaPorId)($matriculaId)->estudiante->nombreCompleto ?? '—';
        }

        return $nombres;
    }

    /**
     * @param  list<int>|null  $grupoIds
     * @return array<int, HorarioDeGrupo>
     */
    private function horarios(?array $grupoIds): array
    {
        $ids = $grupoIds ?? ClaseDictada::query()->distinct()->pluck('grupo_id')->map(intval(...))->all();

        return $ids === [] ? [] : ($this->horariosDeGrupos)(array_values($ids));
    }
}
