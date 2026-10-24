<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Contracts\FichasDePersonas;
use Illuminate\Support\Str;

final readonly class ListarEstudiantesDelGrupo implements EstudiantesDelGrupo
{
    public function __construct(private FichasDePersonas $fichasDePersonas) {}

    public function __invoke(int $grupoId): array
    {
        $matriculas = Matricula::query()->where('grupo_id', $grupoId)->where('estado', EstadoMatricula::Activa)->get(['id', 'persona_id']);
        $fichas = ($this->fichasDePersonas)(array_values(array_map(intval(...), $matriculas->pluck('persona_id')->all())));

        return array_values($matriculas
            ->filter(fn (Matricula $matricula): bool => isset($fichas[$matricula->persona_id]))
            ->map(fn (Matricula $matricula): EstudianteDelGrupo => new EstudianteDelGrupo($matricula->id, $matricula->persona_id, $fichas[$matricula->persona_id]->nombreCompleto))
            ->sortBy(fn (EstudianteDelGrupo $estudiante): string => Str::lower(Str::ascii($estudiante->nombreCompleto)))
            ->all());
    }
}
