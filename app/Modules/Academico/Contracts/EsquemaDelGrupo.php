<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\EsquemaDeEvaluacion;

interface EsquemaDelGrupo
{
    /** The scheme of the group's language and level valid on its period start, the most specific first; null when none applies. */
    public function __invoke(int $grupoId): ?EsquemaDeEvaluacion;
}
