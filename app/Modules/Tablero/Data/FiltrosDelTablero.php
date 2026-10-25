<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Academico\Enums\NivelCurricular;

final readonly class FiltrosDelTablero
{
    public function __construct(
        public int $periodoId,
        public ?int $idiomaId = null,
        public ?NivelCurricular $nivel = null,
    ) {}
}
