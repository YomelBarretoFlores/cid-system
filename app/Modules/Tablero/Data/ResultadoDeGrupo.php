<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Academico\Data\HorarioDeGrupo;

// One group of the period with its program and teacher; enrolled students count as "inscritos" until the acta gives them a final state.
final readonly class ResultadoDeGrupo
{
    public function __construct(
        public HorarioDeGrupo $grupo,
        public string $programa,
        public string $docente,
        public int $inscritos,
        public int $aprobados,
        public int $desaprobados,
        public int $nsp,
    ) {}

    public function total(): int
    {
        return $this->inscritos + $this->aprobados + $this->desaprobados + $this->nsp;
    }
}
