<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Docencia\Enums\CondicionLaboral;

final readonly class DatosDeDocente
{
    /**
     * @param  list<IdiomaHabilitado>  $idiomas
     */
    public function __construct(
        public int $personaId,
        public string $correoInstitucional,
        public string $ruc,
        public string $banco,
        #[\SensitiveParameter]
        public string $cuentaBancaria,
        public CondicionLaboral $condicion,
        public array $idiomas,
    ) {}
}
