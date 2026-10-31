<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\MatriculasCalificadas;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\NotaFinal;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListarMatriculasCalificadas implements MatriculasCalificadas
{
    public function __invoke(array $matriculaIds): array
    {
        return array_values(array_map(intval(...), NotaFinal::query()
            ->whereIn('matricula_id', $matriculaIds)
            ->whereHas('acta', fn (Builder $acta) => $acta->where('estado', EstadoActa::Cerrada))
            ->pluck('matricula_id')
            ->all()));
    }
}
