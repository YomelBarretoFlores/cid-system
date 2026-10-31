<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class GrupoDelHorario
{
    /**
     * @param  list<string>  $horarios
     */
    public function __construct(
        public string $codigo,
        public string $idioma,
        public int $cicloNumero,
        public string $docente,
        public ?string $aula,
        public ?string $enlaceVirtual,
        public array $horarios,
    ) {}
}
