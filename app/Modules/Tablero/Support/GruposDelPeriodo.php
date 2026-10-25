<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

// The period's groups that were not cancelled, with their language and cycle, narrowed by the dashboard filters.
final class GruposDelPeriodo
{
    public static function consulta(FiltrosDelTablero $filtros): Builder
    {
        return self::deTodosLosPeriodos($filtros)->where('g.periodo_id', $filtros->periodoId);
    }

    public static function deTodosLosPeriodos(FiltrosDelTablero $filtros): Builder
    {
        return DB::table('aca_grupos as g')
            ->join('aca_ciclos as c', 'c.id', '=', 'g.ciclo_id')
            ->join('aca_idiomas as i', 'i.id', '=', 'c.idioma_id')
            ->where('g.estado', '!=', EstadoGrupo::Cancelado->value)
            ->when($filtros->idiomaId, fn (Builder $query, int $idiomaId): Builder => $query->where('c.idioma_id', $idiomaId))
            ->when($filtros->nivel, fn (Builder $query, NivelCurricular $nivel): Builder => $query->where('c.nivel', $nivel->value));
    }
}
