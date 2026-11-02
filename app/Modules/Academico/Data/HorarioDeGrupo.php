<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use Carbon\CarbonImmutable;

final readonly class HorarioDeGrupo
{
    public const array RELACIONES = ['ciclo.idioma', 'periodo', 'aula', 'horarios'];

    /**
     * @param  list<SesionDeClase>  $sesiones
     */
    public function __construct(
        public int $grupoId,
        public string $codigo,
        public EstadoGrupo $estado,
        public int $idiomaId,
        public string $idioma,
        public NivelCurricular $nivel,
        public int $cicloNumero,
        public int $periodoId,
        public string $periodo,
        public CarbonImmutable $periodoInicio,
        public CarbonImmutable $periodoFin,
        public array $sesiones,
        public ?string $aulaCodigo = null,
    ) {}

    public static function desde(Grupo $grupo): self
    {
        return new self(
            grupoId: $grupo->id,
            codigo: $grupo->codigo,
            estado: $grupo->estado,
            idiomaId: $grupo->ciclo->idioma_id,
            idioma: $grupo->ciclo->idioma->nombre,
            nivel: $grupo->ciclo->nivel,
            cicloNumero: $grupo->ciclo->numero,
            periodoId: $grupo->periodo_id,
            periodo: $grupo->periodo->nombre,
            periodoInicio: CarbonImmutable::parse($grupo->periodo->fecha_inicio)->startOfDay(),
            periodoFin: CarbonImmutable::parse($grupo->periodo->fecha_fin)->startOfDay(),
            sesiones: array_values($grupo->horarios->map(fn (Horario $horario): SesionDeClase => SesionDeClase::desde($horario))->all()),
            aulaCodigo: $grupo->aula?->codigo,
        );
    }

    public function compartePeriodoCon(self $otro): bool
    {
        return $this->periodoInicio->lte($otro->periodoFin) && $this->periodoFin->gte($otro->periodoInicio);
    }
}
