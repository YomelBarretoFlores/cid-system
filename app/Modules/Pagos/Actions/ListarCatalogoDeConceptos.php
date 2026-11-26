<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\CatalogoDeConceptos;
use App\Modules\Pagos\Data\ConceptoDelCatalogo;
use App\Modules\Pagos\Models\Concepto;

final readonly class ListarCatalogoDeConceptos implements CatalogoDeConceptos
{
    public function __invoke(): array
    {
        return array_values(Concepto::query()->orderBy('nombre')->get()
            ->map(fn (Concepto $concepto): ConceptoDelCatalogo => new ConceptoDelCatalogo(
                id: $concepto->id,
                codigo: $concepto->codigo,
                nombre: $concepto->nombre,
                tipo: $concepto->tipo,
                activo: $concepto->activo,
            ))->all());
    }
}
