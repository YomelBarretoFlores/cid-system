<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class QuitarDocenteDelGrupo
{
    public function __construct(private BloqueoDeGrupo $bloqueoDeGrupo) {}

    public function __invoke(int $grupoId): void
    {
        DB::transaction(function () use ($grupoId): void {
            $grupo = ($this->bloqueoDeGrupo)($grupoId);

            if ($grupo->estado->esTerminal()) {
                throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()} y ya no admite cambios.");
            }

            $carga = CargaAcademica::query()->where('grupo_id', $grupo->grupoId)->first()
                ?? throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} no tiene docente asignado.");

            $carga->delete();
        });
    }
}
