<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Data;

// What staff enter when finishing: the proficiency exam grade, the reclassification cycle or the resolution comment.
final readonly class DatosDeResolucion
{
    public function __construct(
        public ?string $nota = null,
        public ?int $cicloId = null,
        public ?string $comentario = null,
    ) {}
}
