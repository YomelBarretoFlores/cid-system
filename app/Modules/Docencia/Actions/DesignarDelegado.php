<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Docencia\Models\Delegado;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class DesignarDelegado
{
    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
    ) {}

    public function __invoke(int $grupoId, int $docenteId, int $matriculaId, User $usuario): Delegado
    {
        return Transaccion::ejecutar(function () use ($grupoId, $docenteId, $matriculaId, $usuario): Delegado {
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            GrupoACargo::asegurar($grupo, $docenteId, 'el delegado solo se designa');
            $matriculas = array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->matriculaId, ($this->estudiantesDelGrupo)($grupoId));

            if (! in_array($matriculaId, $matriculas, true)) {
                throw new ReglaDeNegocioIncumplida("El estudiante elegido no tiene matrícula activa en el grupo {$grupo->codigo}.");
            }

            return Delegado::query()->updateOrCreate(['grupo_id' => $grupoId], ['matricula_id' => $matriculaId, 'designado_por' => $usuario->id]);
        });
    }
}
