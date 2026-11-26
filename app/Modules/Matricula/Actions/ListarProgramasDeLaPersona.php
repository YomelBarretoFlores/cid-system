<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\ProgramasDeGrupos;
use App\Modules\Academico\Data\ProgramaDelGrupo;
use App\Modules\Matricula\Contracts\ProgramasDeLaPersona;
use App\Modules\Matricula\Models\Matricula;

final readonly class ListarProgramasDeLaPersona implements ProgramasDeLaPersona
{
    public function __construct(private ProgramasDeGrupos $programasDeGrupos) {}

    public function __invoke(int $personaId): array
    {
        $grupoIds = array_values(array_unique(array_map(
            intval(...),
            Matricula::query()->where('persona_id', $personaId)->orderByDesc('matriculado_en')->orderByDesc('id')->pluck('grupo_id')->all(),
        )));
        $programas = ($this->programasDeGrupos)($grupoIds);

        return collect($grupoIds)
            ->map(fn (int $grupoId): ?ProgramaDelGrupo => $programas[$grupoId] ?? null)
            ->filter()
            ->unique(fn (ProgramaDelGrupo $programa): int => $programa->idiomaId)
            ->mapWithKeys(fn (ProgramaDelGrupo $programa): array => [$programa->programaId => $programa->programa])
            ->all();
    }
}
