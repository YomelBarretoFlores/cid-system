<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\VentanaDeMatricula;

interface VentanasDeMatricula
{
    /**
     * @return list<VentanaDeMatricula> enrollment windows that have not ended yet (Lima), soonest first
     */
    public function __invoke(): array;
}
