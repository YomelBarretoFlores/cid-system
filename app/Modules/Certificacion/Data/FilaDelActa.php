<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Certificacion\Enums\EstadoFinal;

final readonly class FilaDelActa
{
    /**
     * @param  array<int, string>  $notas  grade per component id, blanks left out
     */
    public function __construct(
        public int $matriculaId,
        public int $personaId,
        public string $estudiante,
        public string $documento,
        public array $notas,
        public ?string $promedio,
        public EstadoFinal $estadoFinal,
    ) {}
}
