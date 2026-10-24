<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

final readonly class ComponenteDeEvaluacion
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nombre,
        public string $peso,
    ) {}
}
