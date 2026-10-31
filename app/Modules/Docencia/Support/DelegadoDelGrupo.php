<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Docencia\Models\Delegado;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;

// A delegate whose enrollment is no longer active is not a delegate, so the row alone is not enough.
final readonly class DelegadoDelGrupo
{
    public function __construct(private EstudiantesDelGrupo $estudiantesDelGrupo) {}

    public function __invoke(int $grupoId): ?EstudianteDelGrupo
    {
        $matriculaId = Delegado::query()->where('grupo_id', $grupoId)->value('matricula_id');

        if ($matriculaId === null) {
            return null;
        }

        foreach (($this->estudiantesDelGrupo)($grupoId) as $estudiante) {
            if ($estudiante->matriculaId === (int) $matriculaId) {
                return $estudiante;
            }
        }

        return null;
    }
}
