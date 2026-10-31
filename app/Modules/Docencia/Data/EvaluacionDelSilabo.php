<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

final readonly class EvaluacionDelSilabo
{
    public function __construct(
        public string $componenteCodigo,
        public string $componente,
        public string $peso,
        public ?string $indicadores,
        public ?string $instrumento,
        public ?int $semana,
    ) {}
}
