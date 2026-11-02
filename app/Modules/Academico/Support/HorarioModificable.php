<?php

declare(strict_types=1);

namespace App\Modules\Academico\Support;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\ClasesDictadasDelGrupo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class HorarioModificable
{
    public function __construct(private ClasesDictadasDelGrupo $clasesDictadasDelGrupo) {}

    /** Docencia rebuilds past classes from the current weekly schedule, so it cannot change once students or classes depend on it. */
    public function asegurar(Grupo $grupo): void
    {
        if ($grupo->estado->esTerminal()) {
            throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()} y ya no admite cambios.");
        }

        if ($grupo->estado === EstadoGrupo::Programado) {
            return;
        }

        if ($grupo->matriculados > 0 || ($this->clasesDictadasDelGrupo)($grupo->id)) {
            throw new ReglaDeNegocioIncumplida("El horario del grupo {$grupo->codigo} no puede cambiar porque ya tiene estudiantes matriculados o clases dictadas.");
        }
    }
}
