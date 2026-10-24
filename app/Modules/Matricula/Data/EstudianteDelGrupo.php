<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class EstudianteDelGrupo
{
    public function __construct(
        public int $matriculaId,
        public int $personaId,
        public string $nombreCompleto,
    ) {}
}
