<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Academico\Data\SesionDeClase;
use Carbon\CarbonImmutable;

final readonly class ClaseProgramada
{
    public function __construct(
        public CarbonImmutable $fecha,
        public SesionDeClase $sesion,
    ) {}

    public function clave(): string
    {
        return $this->fecha->toDateString().'|'.substr($this->sesion->horaInicio, 0, 5);
    }

    public function descripcion(): string
    {
        return sprintf('%s %s de %s a %s', $this->sesion->dia(), $this->fecha->format('d/m/Y'), substr($this->sesion->horaInicio, 0, 5), substr($this->sesion->horaFin, 0, 5));
    }
}
