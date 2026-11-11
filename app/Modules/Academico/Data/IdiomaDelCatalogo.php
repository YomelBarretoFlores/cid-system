<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

final readonly class IdiomaDelCatalogo
{
    /**
     * @param  array<int, string>  $ciclos  cycle id => "Ciclo 6 (Intermedio)", in study order
     * @param  array<int, int>  $horasPorCiclo  cycle id => teaching hours of the cycle
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public bool $activo,
        public array $ciclos,
        public array $horasPorCiclo = [],
    ) {}
}
