<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

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
        public int $condicionLaboralId,
        public ?int $gradoAcademicoId,
        public array $idiomas,
    ) {}
}
