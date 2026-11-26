<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Data\TarifaVigente;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Contracts\CatalogoDeVinculos;
use App\Modules\Personas\Data\TipoDeVinculo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListarTarifasVigentes implements TarifasVigentes
{
    public function __construct(private CatalogoDeProgramas $programas, private CatalogoDeVinculos $vinculos) {}

    public function __invoke(CarbonInterface $fecha): array
    {
        $programas = array_column(array_map(fn (ProgramaDelCatalogo $programa): array => [$programa->id, $programa->nombre], ($this->programas)()), 1, 0);
        $vinculos = array_column(array_map(fn (TipoDeVinculo $vinculo): array => [$vinculo->codigo, $vinculo->nombre], ($this->vinculos)()), 1, 0);
        $orden = array_flip(array_keys($vinculos));

        $tarifas = Tarifa::query()
            ->vigenteEn($fecha)
            ->whereHas('concepto', fn (Builder $concepto) => $concepto->where('activo', true))
            ->with('concepto.fuenteDeConcepto')
            ->get()
            ->sortBy([
                fn (Tarifa $a, Tarifa $b): int => $a->concepto->nombre <=> $b->concepto->nombre,
                fn (Tarifa $a, Tarifa $b): int => ($orden[$a->tipo_vinculo] ?? PHP_INT_MAX) <=> ($orden[$b->tipo_vinculo] ?? PHP_INT_MAX),
                fn (Tarifa $a, Tarifa $b): int => ($a->programa_id ?? 0) <=> ($b->programa_id ?? 0),
                fn (Tarifa $a, Tarifa $b): int => ($a->nivel?->orden() ?? 0) <=> ($b->nivel?->orden() ?? 0),
            ])
            ->map(fn (Tarifa $tarifa): TarifaVigente => new TarifaVigente(
                codigo: $tarifa->concepto->codigo,
                concepto: $tarifa->concepto->nombre,
                fuente: $tarifa->concepto->fuenteDeConcepto->codigo,
                fuenteEtiqueta: $tarifa->concepto->fuenteDeConcepto->nombre,
                tipoVinculo: $tarifa->tipo_vinculo,
                vinculoEtiqueta: $vinculos[$tarifa->tipo_vinculo] ?? $tarifa->tipo_vinculo,
                monto: (string) $tarifa->monto,
                vigenteDesde: $tarifa->vigente_desde,
                tipo: $tarifa->concepto->tipo,
                nivel: $tarifa->nivel,
                programa: $tarifa->programa_id === null ? null : ($programas[$tarifa->programa_id] ?? null),
            ));

        return array_values($tarifas->all());
    }
}
