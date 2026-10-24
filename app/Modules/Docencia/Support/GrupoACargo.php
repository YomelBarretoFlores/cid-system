<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final class GrupoACargo
{
    public static function tiene(int $docenteId, int $grupoId): bool
    {
        return CargaAcademica::query()->where('grupo_id', $grupoId)->where('docente_id', $docenteId)->exists();
    }

    /** $registro completes the state message, for example "la asistencia solo se registra". */
    public static function asegurar(HorarioDeGrupo $grupo, int $docenteId, string $registro): void
    {
        if (! self::tiene($docenteId, $grupo->grupoId)) {
            throw new ReglaDeNegocioIncumplida("No tienes a cargo el grupo {$grupo->codigo}.");
        }

        if (! in_array($grupo->estado, [EstadoGrupo::Abierto, EstadoGrupo::Cerrado], true)) {
            throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()}; {$registro} en grupos abiertos o cerrados.");
        }
    }
}
