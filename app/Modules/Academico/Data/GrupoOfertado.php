<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;

// Public view of a group: no room, virtual link or teacher.
final readonly class GrupoOfertado
{
    /**
     * @param  list<string>  $horario
     */
    public function __construct(
        public string $idioma,
        public int $cicloNumero,
        public string $nivel,
        public string $modalidad,
        public bool $esAcelerado,
        public array $horario,
        public int $vacantes,
        public bool $abierto,
    ) {}

    public static function desde(Grupo $grupo): self
    {
        return new self(
            idioma: $grupo->ciclo->idioma->nombre,
            cicloNumero: $grupo->ciclo->numero,
            nivel: $grupo->ciclo->nivel->etiqueta(),
            modalidad: $grupo->modalidad->etiqueta(),
            esAcelerado: $grupo->es_acelerado,
            horario: SesionDeClase::compactar(array_values($grupo->horarios->map(fn (Horario $horario): SesionDeClase => SesionDeClase::desde($horario))->all())),
            vacantes: max(0, $grupo->vacantes_disponibles),
            abierto: $grupo->estado === EstadoGrupo::Abierto,
        );
    }
}
