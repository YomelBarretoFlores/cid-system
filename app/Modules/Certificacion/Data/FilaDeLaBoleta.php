<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Enums\EstadoFinal;

final readonly class FilaDeLaBoleta
{
    public function __construct(
        public int $matriculaId,
        public string $grupoCodigo,
        public string $idioma,
        public int $cicloNumero,
        public string $cicloNombre,
        public int $horasLectivas,
        public ?string $promedio,
        public ?EstadoFinal $estadoFinal,
    ) {}

    public function actaCerrada(): bool
    {
        return $this->estadoFinal instanceof EstadoFinal;
    }

    public function estado(): string
    {
        return $this->estadoFinal?->etiqueta() ?? 'Acta abierta';
    }
}
