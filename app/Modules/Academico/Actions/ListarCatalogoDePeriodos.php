<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Models\Periodo;

final readonly class ListarCatalogoDePeriodos implements CatalogoDePeriodos
{
    public function __invoke(): array
    {
        return Periodo::query()->orderByDesc('fecha_inicio')->pluck('nombre', 'id')->all();
    }
}
