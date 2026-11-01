<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Academico\Enums\NivelCurricular;

final readonly class FiltrosDeReporte
{
    public function __construct(
        public int $periodoId,
        public ?int $idiomaId = null,
        public ?NivelCurricular $nivel = null,
        public ?int $grupoId = null,
    ) {}

    public function delTablero(): FiltrosDelTablero
    {
        return new FiltrosDelTablero(periodoId: $this->periodoId, idiomaId: $this->idiomaId, nivel: $this->nivel);
    }
}
