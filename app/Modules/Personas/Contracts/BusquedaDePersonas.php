<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

interface BusquedaDePersonas
{
    /**
     * @return array<int, string>
     */
    public function __invoke(string $termino, int $limite = 50): array;
}
