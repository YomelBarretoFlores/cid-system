<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\NivelCurricular;

final readonly class DatosDeCiclo
{
    public function __construct(
        public int $numero,
        public NivelCurricular $nivel,
        public string $nombre,
        public int $horasLectivas,
        public bool $activo,
    ) {}
}
