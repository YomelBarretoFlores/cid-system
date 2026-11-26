<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Modalidad;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class GuardarGrupo
{
    public function __invoke(DatosDeGrupo $datos, ?int $grupoId = null): Grupo
    {
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

        $this->asegurarModalidad($grupo, $datos);
        $this->asegurarCicloDisponible($grupo, $datos);
        $this->asegurarPrograma($grupo, $datos);

        if ($datos->cupoMaximo < $grupo->matriculados) {
            throw new ReglaDeNegocioIncumplida("El cupo máximo no puede ser menor que los {$grupo->matriculados} estudiantes matriculados.");
        }

        if ($datos->aulaId !== null) {
            $this->asegurarAula($grupo, $datos);
        }

        $grupo->fill([
            'periodo_id' => $datos->periodoId,
            'ciclo_id' => $datos->cicloId,
            'ciclo_hasta_id' => $this->cicloHasta($grupo, $datos),
            'programa_id' => $datos->programaId,
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

    private function asegurarModalidad(Grupo $grupo, DatosDeGrupo $datos): void
    {
        $modalidad = Modalidad::query()->where('codigo', $datos->modalidad)->first();

        // A deactivated modality keeps serving the groups that already use it.
        if ($modalidad === null || (! $modalidad->activo && $grupo->modalidad !== $modalidad->codigo)) {
            throw new ReglaDeNegocioIncumplida('La modalidad elegida no está disponible para nuevos grupos.');
        }

        $nombre = mb_strtolower($modalidad->nombre);

        if ($modalidad->usa_aula) {
            throw_if($datos->aulaId === null, ReglaDeNegocioIncumplida::class, "Un grupo {$nombre} necesita un aula.");

            return;
        }

        throw_if(blank($datos->enlaceVirtual), ReglaDeNegocioIncumplida::class, "Un grupo {$nombre} necesita el enlace de la clase.");
        throw_if($datos->aulaId !== null, ReglaDeNegocioIncumplida::class, "Un grupo {$nombre} no usa aula.");
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

    private function cicloHasta(Grupo $grupo, DatosDeGrupo $datos): ?int
    {
        if (! $datos->esAcelerado) {
            return null;
        }

        // An existing accelerated group keeps its second cycle even after that cycle is deactivated.
        if ($grupo->exists && (int) $grupo->ciclo_id === $datos->cicloId && $grupo->ciclo_hasta_id !== null) {
            return (int) $grupo->ciclo_hasta_id;
        }

        $ciclo = Ciclo::query()->whereKey($datos->cicloId)->firstOrFail();
        $siguiente = Ciclo::query()->siguienteDelNivel($ciclo)->value('id');

        return $siguiente === null
            ? throw new ReglaDeNegocioIncumplida("Un grupo acelerado cubre el ciclo elegido y el siguiente del mismo nivel; {$ciclo->nombre} no tiene ciclo siguiente en su nivel.")
            : (int) $siguiente;
    }

    private function asegurarPrograma(Grupo $grupo, DatosDeGrupo $datos): void
    {
        $programa = Programa::query()->whereKey($datos->programaId)->firstOrFail();

        // A deactivated program keeps serving the groups that already belong to it.
        if (! $programa->activo && (int) $grupo->programa_id !== $programa->id) {
            throw new ReglaDeNegocioIncumplida("El programa {$programa->nombre} no está disponible para nuevos grupos.");
        }

        if (! $programa->regimen->admiteGrupo($datos->esAcelerado)) {
            throw new ReglaDeNegocioIncumplida($datos->esAcelerado
                ? "El programa {$programa->nombre} no admite grupos acelerados."
                : "El programa {$programa->nombre} solo admite grupos acelerados.");
        }
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
            $this->asegurarAulaLibre($grupo, $aula, Periodo::query()->findOrFail($datos->periodoId));
        }
    }

    private function asegurarAulaLibre(Grupo $grupo, Aula $aula, Periodo $periodo): void
    {
        foreach ($grupo->horarios as $horario) {
            $choque = Horario::query()
                ->with('grupo')
                ->ocupanAula($aula->id, $periodo, $grupo->id)
                ->seCruzaCon($horario->dia_semana, $horario->hora_inicio, $horario->hora_fin)
                ->first();

            if ($choque !== null) {
                throw new ReglaDeNegocioIncumplida("El aula {$aula->codigo} ya está ocupada por el grupo {$choque->grupo->codigo} el {$choque->descripcion}.");
            }
        }
    }
}
