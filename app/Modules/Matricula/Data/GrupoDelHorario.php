<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class GrupoDelHorario
{
    /**
     * @param  list<string>  $horarios
     */
    public function __construct(
        public string $numero,
        public int $color,
        public string $codigo,
        public string $idioma,
        public int $cicloNumero,
        public string $nivel,
        public string $modalidad,
        public string $docente,
        public ?string $aula,
        public ?string $aulaCodigo,
        public ?string $enlaceVirtual,
        public array $horarios,
    ) {}

    public function lugar(): string
    {
        return $this->aula ?? $this->sinAula();
    }

    // The grid cell is short and in capitals, as in the SGA: the classroom code, VIRTUAL or POR ASIGNAR.
    public function lugarEnLaCuadricula(): string
    {
        return $this->aulaCodigo ?? mb_strtoupper($this->sinAula());
    }

    private function sinAula(): string
    {
        return $this->enlaceVirtual === null ? 'Por asignar' : 'Virtual';
    }
}
