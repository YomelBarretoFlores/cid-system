<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;

final readonly class FichaDeGrupo
{
    public const array RELACIONES = ['ciclo.idioma', 'modalidadDeDictado', 'periodo', 'programa', 'aula', 'horarios'];

    /**
     * @param  list<string>  $horarios
     * @param  list<SesionDeClase>  $sesiones
     */
    public function __construct(
        public int $id,
        public string $codigo,
        public string $idioma,
        public int $cicloNumero,
        public string $nivel,
        public string $modalidad,
        public bool $esAcelerado,
        public int $periodoId,
        public string $periodo,
        public array $horarios,
        public ?string $aula,
        public ?string $enlaceVirtual,
        public int $vacantesDisponibles,
        public array $sesiones,
        public ?string $aulaCodigo,
        public string $programa,
        public ?int $cicloNumeroHasta = null,
    ) {}

    public static function desde(Grupo $grupo): self
    {
        return new self(
            id: $grupo->id,
            codigo: $grupo->codigo,
            idioma: $grupo->ciclo->idioma->nombre,
            cicloNumero: $grupo->ciclo->numero,
            nivel: $grupo->ciclo->nivel->etiqueta(),
            modalidad: $grupo->modalidadDeDictado->nombre,
            esAcelerado: $grupo->es_acelerado,
            periodoId: $grupo->periodo_id,
            periodo: $grupo->periodo->nombre,
            horarios: array_values($grupo->horarios->map(fn (Horario $horario): string => $horario->descripcion)->all()),
            aula: $grupo->aula === null ? null : "{$grupo->aula->codigo} · {$grupo->aula->nombre}",
            enlaceVirtual: $grupo->enlace_virtual,
            vacantesDisponibles: max(0, $grupo->vacantes_disponibles),
            sesiones: array_values($grupo->horarios->map(fn (Horario $horario): SesionDeClase => SesionDeClase::desde($horario))->all()),
            aulaCodigo: $grupo->aula?->codigo,
            programa: $grupo->programa->nombre,
            // The aca_grupos_ciclo_hasta trigger keeps the second cycle of an accelerated group as the next one.
            cicloNumeroHasta: $grupo->es_acelerado ? $grupo->ciclo->numero + 1 : null,
        );
    }

    /**
     * @return list<string>
     */
    public function horarioCompacto(): array
    {
        return SesionDeClase::compactar($this->sesiones);
    }
}
