<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;

final readonly class FichaDeGrupo
{
    public const array RELACIONES = ['ciclo.idioma', 'periodo', 'aula', 'horarios'];

    /**
     * @param  list<string>  $horarios
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
    ) {}

    public static function desde(Grupo $grupo): self
    {
        return new self(
            id: $grupo->id,
            codigo: $grupo->codigo,
            idioma: $grupo->ciclo->idioma->nombre,
            cicloNumero: $grupo->ciclo->numero,
            nivel: $grupo->ciclo->nivel->etiqueta(),
            modalidad: $grupo->modalidad->etiqueta(),
            esAcelerado: $grupo->es_acelerado,
            periodoId: $grupo->periodo_id,
            periodo: $grupo->periodo->nombre,
            horarios: array_values($grupo->horarios->map(fn (Horario $horario): string => $horario->descripcion)->all()),
            aula: $grupo->aula === null ? null : "{$grupo->aula->codigo} · {$grupo->aula->nombre}",
            enlaceVirtual: $grupo->enlace_virtual,
            vacantesDisponibles: max(0, $grupo->vacantes_disponibles),
        );
    }
}
