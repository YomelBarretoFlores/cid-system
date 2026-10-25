<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Grupo;
use Carbon\CarbonImmutable;

final readonly class DatosDelCurso
{
    public const array RELACIONES = ['ciclo.idioma', 'periodo'];

    public function __construct(
        public int $grupoId,
        public string $codigo,
        public EstadoGrupo $estado,
        public string $idioma,
        public NivelCurricular $nivel,
        public int $cicloNumero,
        public string $cicloNombre,
        public int $horasLectivas,
        public int $periodoId,
        public string $periodo,
        public ?CarbonImmutable $actasInicio,
        public ?CarbonImmutable $actasFin,
    ) {}

    public static function desde(Grupo $grupo): self
    {
        $periodo = $grupo->periodo;

        return new self(
            grupoId: $grupo->id,
            codigo: $grupo->codigo,
            estado: $grupo->estado,
            idioma: $grupo->ciclo->idioma->nombre,
            nivel: $grupo->ciclo->nivel,
            cicloNumero: $grupo->ciclo->numero,
            cicloNombre: $grupo->ciclo->nombre,
            horasLectivas: $grupo->ciclo->horas_lectivas,
            periodoId: $periodo->id,
            periodo: $periodo->nombre,
            actasInicio: $periodo->actas_inicio === null ? null : CarbonImmutable::parse($periodo->actas_inicio->toDateString(), 'America/Lima'),
            actasFin: $periodo->actas_fin === null ? null : CarbonImmutable::parse($periodo->actas_fin->toDateString(), 'America/Lima'),
        );
    }

    public function admiteActasEl(CarbonImmutable $momento): bool
    {
        if (! $this->actasInicio instanceof CarbonImmutable || ! $this->actasFin instanceof CarbonImmutable) {
            return false;
        }

        $dia = $momento->setTimezone('America/Lima')->toDateString();

        return $dia >= $this->actasInicio->toDateString() && $dia <= $this->actasFin->toDateString();
    }
}
