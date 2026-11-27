<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

final readonly class CuentaDelPortal
{
    public function __construct(
        public int $personaId,
        public int $usuarioId,
        public ?string $usuario,
        public ?string $correo,
        public bool $desactivada,
    ) {}
}
