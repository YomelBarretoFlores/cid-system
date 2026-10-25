<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\NotasParcialesDelGrupo;
use App\Modules\Docencia\Models\NotaParcial;

final readonly class LeerNotasParcialesDelGrupo implements NotasParcialesDelGrupo
{
    public function __invoke(int $grupoId): array
    {
        $notas = [];

        foreach (NotaParcial::query()->where('grupo_id', $grupoId)->whereNotNull('nota')->orderBy('id')->get() as $nota) {
            $notas[$nota->matricula_id][$nota->componente_id] = (string) $nota->nota;
        }

        return $notas;
    }
}
