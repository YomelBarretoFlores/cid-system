<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Enums\NivelCurricular;

final readonly class CicloAprobadoSinActa
{
    public function __construct(
        public NivelCurricular $nivel,
        public int $numero,
        public string $nombre,
        public ?int $horasLectivas,
    ) {}
}
