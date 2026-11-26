<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

interface AprobacionesPorReclasificacion
{
    /**
     * Records the active cycles before the assigned one as passed by placement (no grade, no hours), like a placement exam, except graded ones; idempotent.
     *
     * @return int cycles recorded now
     */
    public function __invoke(int $personaId, int $cicloId, int $tramiteId, int $usuarioId): int;
}
