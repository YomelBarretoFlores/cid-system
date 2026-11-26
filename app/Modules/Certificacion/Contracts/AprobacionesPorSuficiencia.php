<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Academico\Enums\NivelCurricular;

interface AprobacionesPorSuficiencia
{
    /**
     * Records every active cycle of the level as passed by proficiency with the exam grade, except cycles an acta already graded; idempotent.
     *
     * @return int cycles recorded now
     */
    public function __invoke(int $personaId, string $idioma, NivelCurricular $nivel, string $nota, int $tramiteId, int $usuarioId): int;
}
