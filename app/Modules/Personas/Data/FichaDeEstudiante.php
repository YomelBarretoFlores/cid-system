<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

final readonly class FichaDeEstudiante
{
    public function __construct(
        public int $personaId,
        public string $nombres,
        public string $nombreCompleto,
        public string $documento,
    ) {}
}
