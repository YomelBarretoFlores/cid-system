<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Docencia\Contracts\VerificacionDeCargaDocente;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Support\ReglasDeCargaDocente;

final readonly class VerificarCargaDelGrupo implements VerificacionDeCargaDocente
{
    public function __construct(
        private HorariosDeGrupos $horariosDeGrupos,
        private ReglasDeCargaDocente $reglas,
    ) {}

    public function __invoke(int $grupoId): void
    {
        $carga = CargaAcademica::query()->where('grupo_id', $grupoId)->first();
        $grupo = ($this->horariosDeGrupos)([$grupoId])[$grupoId] ?? null;

        if ($carga === null || $grupo === null || $grupo->estado->esTerminal()) {
            return;
        }

        // Same lock order as an assignment: the caller holds the group row, this takes the teacher row.
        $docente = Docente::query()->whereKey($carga->docente_id)->lockForUpdate()->firstOrFail();

        $this->reglas->asegurarDisponible($docente, $grupo);
        $this->reglas->asegurarSinCruces($docente, $grupo);
    }
}
