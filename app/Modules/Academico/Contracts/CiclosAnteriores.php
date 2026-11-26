<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\CiclosAnterioresAlCiclo;

interface CiclosAnteriores
{
    /**
     * @return CiclosAnterioresAlCiclo the language of the cycle and its active cycles numbered before it, in study order
     */
    public function __invoke(int $cicloId): CiclosAnterioresAlCiclo;
}
