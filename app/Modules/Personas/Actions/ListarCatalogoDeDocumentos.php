<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\CatalogoDeDocumentos;
use App\Modules\Personas\Data\TipoDeDocumento;
use App\Modules\Personas\Models\TipoDocumento;

final readonly class ListarCatalogoDeDocumentos implements CatalogoDeDocumentos
{
    public function __invoke(): array
    {
        return array_values(TipoDocumento::query()->orderBy('orden')->orderBy('nombre')->get()
            ->map(fn (TipoDocumento $tipo): TipoDeDocumento => new TipoDeDocumento(
                $tipo->codigo, $tipo->nombre, $tipo->solo_digitos, $tipo->longitud_minima, $tipo->longitud_maxima, $tipo->activo, $tipo->por_defecto,
            ))
            ->all());
    }
}
