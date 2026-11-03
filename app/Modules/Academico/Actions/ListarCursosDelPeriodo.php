<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CursosDelPeriodo;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;

final readonly class ListarCursosDelPeriodo implements CursosDelPeriodo
{
    public function __invoke(int $periodoId): array
    {
        return array_values(Grupo::query()
            ->with(DatosDelCurso::RELACIONES)
            ->where('periodo_id', $periodoId)
            ->where('estado', '!=', EstadoGrupo::Cancelado)
            ->get()
            ->map(fn (Grupo $grupo): DatosDelCurso => DatosDelCurso::desde($grupo))
            ->sortBy([['idioma', 'asc'], ['cicloNumero', 'asc'], ['codigo', 'asc']])
            ->all());
    }
}
