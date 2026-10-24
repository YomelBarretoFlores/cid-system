<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Query\Builder;

final readonly class SeleccionarIdsDePersonas implements SubconsultaDePersonas
{
    public function __invoke(string $termino): Builder
    {
        $consulta = Persona::query()->select('id');

        return (trim($termino) === '' ? $consulta->whereRaw('false') : $consulta->coincideCon($termino))->toBase();
    }
}
