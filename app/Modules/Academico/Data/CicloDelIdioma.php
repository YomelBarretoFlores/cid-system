<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\NivelCurricular;

final readonly class CicloDelIdioma
{
    public function __construct(
        public int $id,
        public int $numero,
        public NivelCurricular $nivel,
        public string $nombre,
        public int $horasLectivas,
        public bool $activo,
    ) {}
}
