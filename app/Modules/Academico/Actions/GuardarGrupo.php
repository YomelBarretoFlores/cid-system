<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class GuardarGrupo
{
    public function __invoke(DatosDeGrupo $datos, ?int $grupoId = null): Grupo
    {
        $this->asegurarModalidad($datos);

        if ($datos->cupoMinimo > $datos->cupoMaximo) {
            throw new ReglaDeNegocioIncumplida('El cupo mínimo no puede superar el cupo máximo.');
        }

        try {
            return Transaccion::ejecutar(fn (): Grupo => $this->guardar($datos, $grupoId));
        } catch (UniqueConstraintViolationException $error) {
            throw RestriccionUnica::es($error, 'aca_grupos_periodo_id_codigo_unique')
                ? new ReglaDeNegocioIncumplida("Ya existe un grupo con el código {$datos->codigo} en este periodo.")
                : $error;
        }
    }

    private function guardar(DatosDeGrupo $datos, ?int $grupoId): Grupo
    {
        $grupo = $grupoId === null ? new Grupo : Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();

        if ($grupo->exists && $grupo->estado->esTerminal()) {
            throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()} y ya no admite cambios.");
        }

        $this->asegurarCicloDisponible($grupo, $datos);

        if ($datos->cupoMaximo < $grupo->matriculados) {
            throw new ReglaDeNegocioIncumplida("El cupo máximo no puede ser menor que los {$grupo->matriculados} estudiantes matriculados.");
        }

        if ($datos->aulaId !== null) {
            $this->asegurarAula($grupo, $datos);
        }

        $grupo->fill([
            'periodo_id' => $datos->periodoId,
            'ciclo_id' => $datos->cicloId,
            'aula_id' => $datos->aulaId,
            'codigo' => $datos->codigo,
            'modalidad' => $datos->modalidad,
            'es_acelerado' => $datos->esAcelerado,
            'enlace_virtual' => $datos->enlaceVirtual,
            'cupo_maximo' => $datos->cupoMaximo,
            'cupo_minimo' => $datos->cupoMinimo,
        ])->save();

        return $grupo;
    }

    private function asegurarModalidad(DatosDeGrupo $datos): void
    {
        if ($datos->modalidad !== Modalidad::Virtual) {
            throw_if($datos->aulaId === null, ReglaDeNegocioIncumplida::class, 'Un grupo presencial o semipresencial necesita un aula.');

            return;
        }

        throw_if(blank($datos->enlaceVirtual), ReglaDeNegocioIncumplida::class, 'Un grupo virtual necesita el enlace de la clase.');
        throw_if($datos->aulaId !== null, ReglaDeNegocioIncumplida::class, 'Un grupo virtual no usa aula.');
    }

    private function asegurarCicloDisponible(Grupo $grupo, DatosDeGrupo $datos): void
    {
        if ($grupo->exists && (int) $grupo->ciclo_id === $datos->cicloId) {
            return;
        }

        $ciclo = Ciclo::query()->with('idioma')->whereKey($datos->cicloId)->firstOrFail();

        throw_unless($ciclo->activo, ReglaDeNegocioIncumplida::class, 'El ciclo seleccionado no está disponible para nuevos grupos.');
        throw_unless($ciclo->idioma->activo, ReglaDeNegocioIncumplida::class, "El idioma {$ciclo->idioma->nombre} no está disponible para nuevos grupos.");
    }

    private function asegurarAula(Grupo $grupo, DatosDeGrupo $datos): void
    {
        $aula = Aula::query()->whereKey($datos->aulaId)->lockForUpdate()->firstOrFail();

        // A deactivated classroom keeps serving the groups that already use it.
        if (! $aula->activo && $grupo->aula_id !== $aula->id) {
            throw new ReglaDeNegocioIncumplida("El aula {$aula->codigo} no está disponible para nuevos grupos.");
        }

        if ($datos->cupoMaximo > $aula->capacidad) {
            throw new ReglaDeNegocioIncumplida("El aula {$aula->codigo} tiene capacidad para {$aula->capacidad} estudiantes; el cupo máximo no puede superarla.");
        }

        $cambiaDeAula = $grupo->aula_id !== $aula->id || $grupo->periodo_id !== $datos->periodoId;

        if ($grupo->exists && $cambiaDeAula) {
            $this->asegurarAulaLibre($grupo, $aula, $datos->periodoId);
        }
    }

    private function asegurarAulaLibre(Grupo $grupo, Aula $aula, int $periodoId): void
    {
        foreach ($grupo->horarios as $horario) {
            $choque = Horario::query()
                ->with('grupo')
                ->ocupanAula($aula->id, $periodoId, $grupo->id)
                ->seCruzaCon($horario->dia_semana, $horario->hora_inicio, $horario->hora_fin)
                ->first();

            if ($choque !== null) {
                throw new ReglaDeNegocioIncumplida("El aula {$aula->codigo} ya está ocupada por el grupo {$choque->grupo->codigo} el {$choque->descripcion}.");
            }
        }
    }
}
