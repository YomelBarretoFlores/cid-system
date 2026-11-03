<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CiclosDelNivel;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;

// Grade sheets keep the language by name, so the lookup is by name; a renamed language is found through a group of its sheets.
final readonly class ListarCiclosDelNivel implements CiclosDelNivel
{
    public function __invoke(string $idioma, NivelCurricular $nivel, ?int $grupoId = null): array
    {
        $idiomaId = Idioma::query()->where('nombre', $idioma)->value('id') ?? $this->idiomaDelGrupo($grupoId);

        if ($idiomaId === null) {
            return [];
        }

        return array_values(Ciclo::query()
            ->where(['idioma_id' => $idiomaId, 'nivel' => $nivel, 'activo' => true])
            ->orderBy('numero')
            ->pluck('numero')
            ->map(intval(...))
            ->all());
    }

    private function idiomaDelGrupo(?int $grupoId): ?int
    {
        if ($grupoId === null) {
            return null;
        }

        $idiomaId = Grupo::query()->join('aca_ciclos', 'aca_ciclos.id', '=', 'aca_grupos.ciclo_id')->where('aca_grupos.id', $grupoId)->value('aca_ciclos.idioma_id');

        return $idiomaId === null ? null : (int) $idiomaId;
    }
}
