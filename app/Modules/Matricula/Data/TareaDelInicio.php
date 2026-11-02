<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

// One row of the student home: something the student has to do, or something waiting on the CILN (then it carries its state badge).
final readonly class TareaDelInicio
{
    public function __construct(
        public string $titulo,
        public string $detalle,
        public string $accion,
        public string $ruta,
        public ?string $estado = null,
    ) {}
}
