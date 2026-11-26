<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\CatalogoDeVinculos;
use App\Modules\Personas\Data\TipoDeVinculo;
use App\Modules\Personas\Models\TipoVinculo;

final readonly class ListarCatalogoDeVinculos implements CatalogoDeVinculos
{
    public function __invoke(): array
    {
        return array_values(TipoVinculo::query()->orderBy('orden')->orderBy('nombre')->get()
            ->map(fn (TipoVinculo $vinculo): TipoDeVinculo => new TipoDeVinculo($vinculo->codigo, $vinculo->nombre, $vinculo->activo, $vinculo->por_defecto))
            ->all());
    }
}
