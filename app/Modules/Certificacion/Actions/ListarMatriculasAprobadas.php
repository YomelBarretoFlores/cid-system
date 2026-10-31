<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\MatriculasAprobadas;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\NotaFinal;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListarMatriculasAprobadas implements MatriculasAprobadas
{
    public function __invoke(): array
    {
        return array_values(array_map(intval(...), NotaFinal::query()
            ->where('estado_final', EstadoFinal::Aprobado)
            ->whereHas('acta', fn (Builder $acta) => $acta->where('estado', EstadoActa::Cerrada))
            ->orderBy('matricula_id')
            ->pluck('matricula_id')
            ->all()));
    }
}
