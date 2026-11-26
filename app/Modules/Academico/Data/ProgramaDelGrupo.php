<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

final readonly class ProgramaDelGrupo
{
    public function __construct(
        public int $idiomaId,
        public int $programaId,
        public string $programa,
    ) {}
}
