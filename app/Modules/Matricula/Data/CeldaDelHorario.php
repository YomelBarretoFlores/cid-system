<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class CeldaDelHorario
{
    public function __construct(
        public string $numero,
        public string $lugar,
        public int $color,
    ) {}

    public function etiqueta(): string
    {
        return "[{$this->numero}] [{$this->lugar}]";
    }
}
