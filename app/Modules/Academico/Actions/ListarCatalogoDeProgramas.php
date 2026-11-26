<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Academico\Models\Programa;

final readonly class ListarCatalogoDeProgramas implements CatalogoDeProgramas
{
    public function __invoke(): array
    {
        return array_values(Programa::query()->orderBy('nombre')->get()
            ->map(fn (Programa $programa): ProgramaDelCatalogo => new ProgramaDelCatalogo(
                id: $programa->id,
                codigo: $programa->codigo,
                nombre: $programa->nombre,
                activo: $programa->activo,
            ))->all());
    }
}
