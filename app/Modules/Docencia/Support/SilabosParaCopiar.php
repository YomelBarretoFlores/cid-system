<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Contracts\CiclosDeGrupos;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\Silabo;

// Published syllabi of other groups of the same cycle, any teacher, latest period first.
final readonly class SilabosParaCopiar
{
    public function __construct(
        private CiclosDeGrupos $ciclosDeGrupos,
        private HorariosDeGrupos $horariosDeGrupos,
        private DocentesDeGrupos $docentesDeGrupos,
    ) {}

    /**
     * @return array<int, string> syllabus id => group, period and teacher
     */
    public function __invoke(int $grupoId): array
    {
        $publicados = Silabo::query()->where('estado', EstadoSilabo::Publicado)->where('grupo_id', '!=', $grupoId)->pluck('grupo_id', 'id')->map(intval(...));
        $ciclos = ($this->ciclosDeGrupos)([$grupoId, ...$publicados->values()->unique()->values()->all()]);
        $ciclo = $ciclos[$grupoId]->cicloId ?? 0;
        $mismoCiclo = $publicados->filter(fn (int $grupo): bool => ($ciclos[$grupo]->cicloId ?? -1) === $ciclo);
        $grupoIds = array_values($mismoCiclo->all());
        $horarios = ($this->horariosDeGrupos)($grupoIds);
        $docentes = ($this->docentesDeGrupos)($grupoIds);

        return $mismoCiclo->filter(fn (int $grupo): bool => isset($horarios[$grupo]))
            ->sortBy([fn (int $uno, int $otro): int => $horarios[$otro]->periodoInicio <=> $horarios[$uno]->periodoInicio])
            ->map(fn (int $grupo): string => $this->etiqueta($horarios[$grupo], $docentes[$grupo] ?? 'Sin docente'))
            ->all();
    }

    private function etiqueta(HorarioDeGrupo $grupo, string $docente): string
    {
        return "Grupo {$grupo->codigo} · {$grupo->periodo} · {$docente}";
    }
}
