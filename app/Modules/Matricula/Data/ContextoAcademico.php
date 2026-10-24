<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class ContextoAcademico
{
    public function __construct(
        public ?int $periodoId,
        public ?string $periodo,
        public string $idioma,
        public ?string $nivel,
    ) {}
}
