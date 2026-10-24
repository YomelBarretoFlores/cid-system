<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\ReservaDeVacante;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class ReservarVacante implements ReservaDeVacante
{
    public function __invoke(int $grupoId): GrupoData
    {
        return DB::transaction(function () use ($grupoId): GrupoData {
            $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();

            if (! $this->matriculaAbierta($grupo)) {
                throw new ReglaDeNegocioIncumplida('La matrícula de este grupo no está abierta.');
            }

            if ($grupo->vacantes_disponibles < 1) {
                throw new ReglaDeNegocioIncumplida('El grupo no tiene vacantes disponibles.');
            }

            $grupo->increment('matriculados');

            return GrupoData::desde($grupo);
        });
    }

    private function matriculaAbierta(Grupo $grupo): bool
    {
        $periodo = $grupo->periodo;
        $hoy = CarbonImmutable::today('America/Lima')->toDateString();

        return $grupo->estado === EstadoGrupo::Abierto
            && $periodo->estado === EstadoPeriodo::MatriculaAbierta
            && $hoy >= $periodo->matricula_inicio->toDateString()
            && $hoy <= $periodo->matricula_fin->toDateString();
    }
}
