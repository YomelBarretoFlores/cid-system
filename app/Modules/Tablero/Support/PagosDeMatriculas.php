<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Personas\Contracts\CatalogoDeVinculos;
use App\Modules\Personas\Data\TipoDeVinculo;
use Illuminate\Support\Facades\DB;
use stdClass;

// The enrollments of some groups with the validated payment each used: amount, receipt, place (payment method) and the link type of the fee charged.
final readonly class PagosDeMatriculas
{
    public const string SIN_PAGO = 'Sin pago';

    public function __construct(private CatalogoDeVinculos $catalogoDeVinculos) {}

    /**
     * @param  list<int>  $grupoIds
     * @return list<stdClass> grupo_id, persona_id, monto, numero_operacion, lugar, vinculo
     */
    public function de(array $grupoIds): array
    {
        $vinculos = collect(($this->catalogoDeVinculos)())->mapWithKeys(fn (TipoDeVinculo $vinculo): array => [$vinculo->codigo => $vinculo->nombre])->all();

        return array_values(DB::table('mat_matriculas as m')
            ->leftJoin('pag_pagos as p', fn ($union) => $union->on('p.id', '=', 'm.pago_id')->where('p.estado', EstadoPago::Validado->value))
            ->leftJoin('pag_medios_pago as mp', 'mp.id', '=', 'p.medio_pago_id')
            ->leftJoin('pag_tarifas as t', 't.id', '=', 'p.tarifa_id')
            ->whereIn('m.grupo_id', $grupoIds)
            ->orderBy('m.id')
            ->get(['m.grupo_id', 'm.persona_id', 'p.id as pago_id', 'p.monto', 'p.numero_operacion', 'mp.nombre as lugar', 't.tipo_vinculo'])
            ->map(function (stdClass $fila) use ($vinculos): stdClass {
                $fila->lugar ??= self::SIN_PAGO;
                $fila->vinculo = $fila->pago_id === null ? self::SIN_PAGO : ($vinculos[$fila->tipo_vinculo] ?? 'Sin tarifa');

                return $fila;
            })
            ->all());
    }
}
