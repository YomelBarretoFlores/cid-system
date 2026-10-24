<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\FichaDeGrupo;

interface OfertaDeMatricula
{
    /**
     * Languages missing from the map offer their first cycle.
     *
     * @param  array<int, int>  $ciclosHabilitados  language id => enabled cycle id
     * @return list<FichaDeGrupo>
     */
    public function __invoke(array $ciclosHabilitados): array;
}
