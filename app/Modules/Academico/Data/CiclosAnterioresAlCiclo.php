<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

final readonly class CiclosAnterioresAlCiclo
{
    /**
     * @param  list<DatosDeCiclo>  $ciclos
     */
    public function __construct(
        public string $idioma,
        public array $ciclos,
    ) {}
}
