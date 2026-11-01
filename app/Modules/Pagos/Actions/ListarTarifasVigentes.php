<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Data\TarifaVigente;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListarTarifasVigentes implements TarifasVigentes
{
    public function __invoke(CarbonInterface $fecha): array
    {
        $orden = array_flip(array_column(TipoVinculo::cases(), 'value'));

        $tarifas = Tarifa::query()
            ->vigenteEn($fecha)
            ->whereHas('concepto', fn (Builder $concepto) => $concepto->where('activo', true))
            ->with('concepto.fuenteDeConcepto')
            ->get()
            ->sortBy([
                fn (Tarifa $a, Tarifa $b): int => $a->concepto->nombre <=> $b->concepto->nombre,
                fn (Tarifa $a, Tarifa $b): int => $orden[$a->tipo_vinculo->value] <=> $orden[$b->tipo_vinculo->value],
            ])
            ->map(fn (Tarifa $tarifa): TarifaVigente => new TarifaVigente(
                codigo: $tarifa->concepto->codigo,
                concepto: $tarifa->concepto->nombre,
                fuente: $tarifa->concepto->fuenteDeConcepto->codigo,
                fuenteEtiqueta: $tarifa->concepto->fuenteDeConcepto->nombre,
                tipoVinculo: $tarifa->tipo_vinculo,
                monto: (string) $tarifa->monto,
                vigenteDesde: $tarifa->vigente_desde,
            ));

        return array_values($tarifas->all());
    }
}
