<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Pagos\Enums\EstadoPago;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

// Validated payments with the group (enrollment fee) or the language (placement exam) they paid for.
final class PagosConContexto
{
    public static function validados(): Builder
    {
        return DB::table('pag_pagos as p')
            ->leftJoin('mat_matriculas as m', 'm.pago_id', '=', 'p.id')
            ->leftJoin('aca_grupos as g', 'g.id', '=', 'm.grupo_id')
            ->leftJoin('aca_ciclos as c', 'c.id', '=', 'g.ciclo_id')
            ->leftJoin('mat_solicitudes_clasificacion as s', 's.pago_id', '=', 'p.id')
            ->leftJoin('aca_idiomas as i', 'i.id', '=', DB::raw('coalesce(c.idioma_id, s.idioma_id)'))
            ->where('p.estado', EstadoPago::Validado->value);
    }
}
