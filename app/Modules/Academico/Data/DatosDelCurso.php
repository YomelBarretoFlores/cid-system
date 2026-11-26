<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Grupo;
use Carbon\CarbonImmutable;

final readonly class DatosDelCurso
{
    public const array RELACIONES = ['ciclo.idioma', 'cicloHasta', 'periodo', 'programa'];

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
        public CarbonImmutable $fechaInicio,
        public ?CarbonImmutable $actasInicio,
        public ?CarbonImmutable $actasFin,
        public string $programa,
        public ?int $cicloNumeroHasta = null,
        public ?string $cicloNombreHasta = null,
        public ?int $horasLectivasHasta = null,
    ) {}

    public static function desde(Grupo $grupo): self
    {
        $periodo = $grupo->periodo;
        $hasta = $grupo->cicloHasta;

        return new self(
            grupoId: $grupo->id,
            codigo: $grupo->codigo,
            estado: $grupo->estado,
            idioma: $grupo->ciclo->idioma->nombre,
            nivel: $grupo->ciclo->nivel,
            cicloNumero: $grupo->ciclo->numero,
            cicloNombre: $grupo->ciclo->nombre,
            horasLectivas: $grupo->ciclo->horas_lectivas + ($hasta->horas_lectivas ?? 0),
            periodoId: $periodo->id,
            periodo: $periodo->nombre,
            fechaInicio: CarbonImmutable::parse($periodo->fecha_inicio->toDateString(), 'America/Lima'),
            actasInicio: $periodo->actas_inicio === null ? null : CarbonImmutable::parse($periodo->actas_inicio->toDateString(), 'America/Lima'),
            actasFin: $periodo->actas_fin === null ? null : CarbonImmutable::parse($periodo->actas_fin->toDateString(), 'America/Lima'),
            programa: $grupo->programa->nombre,
            cicloNumeroHasta: $hasta?->numero,
            cicloNombreHasta: $hasta?->nombre,
            horasLectivasHasta: $hasta?->horas_lectivas,
        );
    }

    // An accelerated group covers its cycle and the next one with a single grade.
    public function ciclos(): string
    {
        return $this->cicloNombreHasta === null ? $this->cicloNombre : "{$this->cicloNombre} y {$this->cicloNombreHasta}";
    }

    public function etiquetaDelCiclo(): string
    {
        return $this->cicloNumeroHasta === null ? "Ciclo {$this->cicloNumero}" : "Ciclos {$this->cicloNumero} y {$this->cicloNumeroHasta}";
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
