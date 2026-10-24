<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use Illuminate\Database\Query\Builder;

interface SubconsultaDePersonas
{
    /** Selects the IDs of every matching person, for `whereIn` without loading them. */
    public function __invoke(string $termino): Builder;
}
