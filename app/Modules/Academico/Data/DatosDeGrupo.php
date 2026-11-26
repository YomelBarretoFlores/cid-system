<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\Modalidad;

final readonly class DatosDeGrupo
{
    public function __construct(
        public int $periodoId,
        public int $cicloId,
        public string $codigo,
        public Modalidad $modalidad,
        public bool $esAcelerado,
        public int $cupoMaximo,
        public int $cupoMinimo,
        public int $programaId,
        public ?int $aulaId = null,
        public ?string $enlaceVirtual = null,
    ) {}
}
