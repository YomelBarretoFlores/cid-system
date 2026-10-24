<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Academico\Models\Grupo;

final readonly class ListarCatalogoDeGrupos implements CatalogoDeGrupos
{
    public function __invoke(): array
    {
        return Grupo::query()
            ->join('aca_periodos', 'aca_periodos.id', '=', 'aca_grupos.periodo_id')
            ->orderByDesc('aca_periodos.fecha_inicio')
            ->orderBy('aca_grupos.codigo')
            ->get(['aca_grupos.id', 'aca_grupos.codigo', 'aca_periodos.nombre as periodo'])
            ->mapWithKeys(fn (Grupo $grupo): array => [$grupo->id => "{$grupo->codigo} · {$grupo->getAttribute('periodo')}"])
            ->all();
    }
}
