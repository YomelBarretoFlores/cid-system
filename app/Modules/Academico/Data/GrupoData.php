<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Models\Grupo;

final readonly class GrupoData
{
    public function __construct(
        public int $id,
        public int $periodoId,
        public int $cicloId,
        public int $cicloNumero,
        public int $idiomaId,
    ) {}

    public static function desde(Grupo $grupo): self
    {
        return new self($grupo->id, $grupo->periodo_id, $grupo->ciclo_id, $grupo->ciclo->numero, $grupo->ciclo->idioma_id);
    }
}
