<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\FilaDeLaBoleta;

interface BoletaDeNotas
{
    /**
     * @param  array<int, int>  $grupoPorMatricula  group id by enrollment id
     * @return list<FilaDeLaBoleta> in the given order; the final grade only for closed actas
     */
    public function __invoke(array $grupoPorMatricula): array;
}
