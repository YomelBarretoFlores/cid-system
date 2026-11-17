<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Enums\GrupoDeComponente;

final readonly class NotaDelComponente
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public GrupoDeComponente $grupo,
        public ?string $nota,
    ) {}
}
