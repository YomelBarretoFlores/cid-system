<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CatalogoDeModalidades;
use App\Modules\Academico\Data\ModalidadDelCatalogo;
use App\Modules\Academico\Models\Modalidad;

final readonly class ListarCatalogoDeModalidades implements CatalogoDeModalidades
{
    public function __invoke(): array
    {
        return array_values(Modalidad::query()->orderBy('orden')->orderBy('nombre')->get()
            ->map(fn (Modalidad $modalidad): ModalidadDelCatalogo => new ModalidadDelCatalogo($modalidad->codigo, $modalidad->nombre, $modalidad->descripcion, $modalidad->activo))
            ->all());
    }
}
