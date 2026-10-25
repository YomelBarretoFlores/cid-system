<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class FilaDeRentabilidad
{
    public function __construct(
        public int $grupoId,
        public string $codigo,
        public string $idioma,
        public string $nivel,
        public int $matriculas,
        public int $ingresosCentimos,
        public int $horasCentesimas,
        public int $costoCentimos,
        public int $horasSinCostoCentesimas,
    ) {}

    public function margen(): int
    {
        return $this->ingresosCentimos - $this->costoCentimos;
    }
}
