<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Matricula\Contracts\FinDeLaUltimaMatricula;
use App\Modules\Matricula\Models\Matricula;
use Carbon\CarbonImmutable;

final readonly class UltimaMatriculaDelIdioma implements FinDeLaUltimaMatricula
{
    public function __construct(private HorariosDeGrupos $horariosDeGrupos) {}

    public function __invoke(int $personaId, string $idioma): ?CarbonImmutable
    {
        $grupoIds = array_values(array_unique(array_map(intval(...), Matricula::query()->where('persona_id', $personaId)->pluck('grupo_id')->all())));

        return collect(($this->horariosDeGrupos)($grupoIds))
            ->filter(fn (HorarioDeGrupo $grupo): bool => $grupo->idioma === $idioma)
            ->map(fn (HorarioDeGrupo $grupo): CarbonImmutable => $grupo->periodoFin)
            ->sort()
            ->last();
    }
}
