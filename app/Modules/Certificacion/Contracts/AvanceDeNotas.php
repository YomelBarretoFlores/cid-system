<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Data\AvanceDeNotasDelEstudiante;

interface AvanceDeNotas
{
    // The pre-acta grades and partial average of one enrollment while the acta is not closed, its final grades once it is.
    public function __invoke(int $grupoId, int $matriculaId): AvanceDeNotasDelEstudiante;
}
