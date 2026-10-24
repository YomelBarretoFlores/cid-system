<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

use App\Modules\Personas\Enums\TipoDocumento;
use Carbon\CarbonImmutable;

final readonly class SolicitudDeCuenta
{
    public function __construct(
        public TipoDocumento $tipoDocumento,
        public string $numeroDocumento,
        public string $apellidoPaterno,
        public ?string $apellidoMaterno,
        public string $nombres,
        public CarbonImmutable $fechaNacimiento,
        public string $correo,
        public ?string $celular,
        #[\SensitiveParameter]
        public string $password,
    ) {}
}
