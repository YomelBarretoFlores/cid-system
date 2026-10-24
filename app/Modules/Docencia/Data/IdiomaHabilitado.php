<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Academico\Enums\NivelCurricular;

final readonly class IdiomaHabilitado
{
    public function __construct(
        public int $idiomaId,
        public NivelCurricular $nivelMaximo,
    ) {}
}
