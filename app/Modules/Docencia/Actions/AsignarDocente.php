<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ReglasDeCargaDocente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class AsignarDocente
{
    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private ReglasDeCargaDocente $reglas,
    ) {}

    public function __invoke(int $grupoId, int $docenteId, User $usuario): CargaAcademica
    {
        return Transaccion::ejecutar(function () use ($grupoId, $docenteId, $usuario): CargaAcademica {
            // Lock order shared with schedule changes: group row first, then the teacher row.
            $grupo = ($this->bloqueoDeGrupo)($grupoId);

            if ($grupo->estado->esTerminal()) {
                throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()} y ya no admite cambios.");
            }

            $docente = Docente::query()->whereKey($docenteId)->lockForUpdate()->firstOrFail();
            $carga = CargaAcademica::query()->firstOrNew(['grupo_id' => $grupo->grupoId]);

            if ($carga->exists && $carga->docente_id === $docente->id) {
                throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} ya está a cargo del docente {$docente->codigo}.");
            }

            $this->reglas->asegurarAsignable($docente, $grupo);
            $carga->fill(['docente_id' => $docente->id, 'asignado_por' => $usuario->id])->save();

            return $carga;
        });
    }
}
