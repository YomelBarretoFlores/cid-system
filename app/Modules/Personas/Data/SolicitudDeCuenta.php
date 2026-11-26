<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

use Carbon\CarbonImmutable;

final readonly class SolicitudDeCuenta
{
    public function __construct(
        public string $tipoDocumento,
        public string $numeroDocumento,
        public string $apellidoPaterno,
        public ?string $apellidoMaterno,
        public string $nombres,
        public CarbonImmutable $fechaNacimiento,
        public string $correo,
        public ?string $celular,
        public bool $aceptaTratamiento,
        #[\SensitiveParameter]
        public string $password,
    ) {}
}
