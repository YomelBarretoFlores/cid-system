<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

final readonly class DatosDePersona
{
    public function __construct(
        public string $tipoDocumento,
        public string $numeroDocumento,
        public string $apellidoPaterno,
        public ?string $apellidoMaterno,
        public string $nombres,
        public ?string $correo = null,
        public ?string $celular = null,
        public ?string $fechaNacimiento = null,
        public ?string $tipoVinculo = null,
        public bool $aceptaTratamiento = false,
    ) {}
}
