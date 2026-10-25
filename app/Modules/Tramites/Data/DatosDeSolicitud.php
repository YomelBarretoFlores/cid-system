<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Data;

final readonly class DatosDeSolicitud
{
    public function __construct(
        public ?int $matriculaId = null,
        public ?string $idioma = null,
        public ?string $nivel = null,
        public ?string $motivo = null,
        public ?string $comentario = null,
    ) {}
}
