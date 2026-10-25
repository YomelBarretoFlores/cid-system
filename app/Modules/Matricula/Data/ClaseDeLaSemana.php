<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class ClaseDeLaSemana
{
    public function __construct(
        public string $idioma,
        public string $hora,
        public ?string $aula,
    ) {}
}
