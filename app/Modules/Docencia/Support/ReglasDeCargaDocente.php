<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Models\Disponibilidad;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\Eloquent\Collection;

// Callers hold the teacher row lock, so the teacher's load cannot change while a rule is checked.
final readonly class ReglasDeCargaDocente
{
    public function __construct(private HorariosDeGrupos $horariosDeGrupos) {}

    public function asegurarAsignable(Docente $docente, HorarioDeGrupo $grupo): void
    {
        if ($docente->estado !== EstadoDocente::Activo) {
            throw new ReglaDeNegocioIncumplida("El docente {$docente->codigo} está {$docente->estado->etiqueta()} y no puede recibir carga académica.");
        }

        $this->asegurarHabilitado($docente, $grupo);
        $this->asegurarDisponible($docente, $grupo);
        $this->asegurarSinCruces($docente, $grupo);
    }

    public function asegurarCargaVigente(Docente $docente): void
    {
        foreach ($this->gruposVigentes($docente) as $grupo) {
            $this->asegurarHabilitado($docente, $grupo);
            $this->asegurarDisponible($docente, $grupo);
        }
    }

    public function asegurarSinGruposVigentes(Docente $docente): void
    {
        $codigos = array_map(fn (HorarioDeGrupo $grupo): string => $grupo->codigo, $this->gruposVigentes($docente));

        if ($codigos !== []) {
            throw new ReglaDeNegocioIncumplida(sprintf('El docente %s tiene grupos a cargo (%s); reasígnalos antes de cambiar su estado.', $docente->codigo, implode(', ', $codigos)));
        }
    }

    public function asegurarSinCruces(Docente $docente, HorarioDeGrupo $grupo): void
    {
        foreach ($this->gruposVigentes($docente, $grupo->grupoId) as $otro) {
            $cruce = $grupo->compartePeriodoCon($otro) ? $this->primerCruce($grupo, $otro) : null;

            if ($cruce instanceof SesionDeClase) {
                throw new ReglaDeNegocioIncumplida("El docente {$docente->codigo} ya dicta el grupo {$otro->codigo} el {$cruce->descripcion()}, que se cruza con el grupo {$grupo->codigo}.");
            }
        }
    }

    public function asegurarDisponible(Docente $docente, HorarioDeGrupo $grupo): void
    {
        $franjas = $docente->disponibilidades()->get();

        foreach ($grupo->sesiones as $sesion) {
            if (! $this->cubre($franjas, $sesion)) {
                throw new ReglaDeNegocioIncumplida("La sesión del {$sesion->descripcion()} del grupo {$grupo->codigo} está fuera de la disponibilidad del docente.");
            }
        }
    }

    /**
     * @return list<HorarioDeGrupo>
     */
    public function gruposVigentes(Docente $docente, ?int $exceptoGrupoId = null): array
    {
        $ids = array_values(array_map(intval(...), $docente->cargas()->when($exceptoGrupoId, fn ($query, int $id) => $query->where('grupo_id', '!=', $id))->pluck('grupo_id')->all()));
        $grupos = array_values(array_filter(($this->horariosDeGrupos)($ids), fn (HorarioDeGrupo $grupo): bool => ! $grupo->estado->esTerminal()));
        usort($grupos, fn (HorarioDeGrupo $a, HorarioDeGrupo $b): int => $a->codigo <=> $b->codigo);

        return $grupos;
    }

    private function asegurarHabilitado(Docente $docente, HorarioDeGrupo $grupo): void
    {
        $habilitacion = $docente->idiomas()->where('idioma_id', $grupo->idiomaId)->first();

        if ($habilitacion === null || $habilitacion->nivel_maximo->orden() < $grupo->nivel->orden()) {
            throw new ReglaDeNegocioIncumplida("El docente {$docente->codigo} no está habilitado para dictar {$grupo->idioma} hasta el nivel {$grupo->nivel->etiqueta()} que requiere el grupo {$grupo->codigo}.");
        }
    }

    private function primerCruce(HorarioDeGrupo $grupo, HorarioDeGrupo $otro): ?SesionDeClase
    {
        foreach ($otro->sesiones as $sesionDelOtro) {
            foreach ($grupo->sesiones as $sesion) {
                if ($sesion->seCruzaCon($sesionDelOtro)) {
                    return $sesionDelOtro;
                }
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Disponibilidad>  $franjas
     */
    private function cubre(Collection $franjas, SesionDeClase $sesion): bool
    {
        // Adjacent windows (16:00-19:00 and 19:00-22:00) are one continuous availability.
        $cubiertaHasta = $sesion->horaInicio;
        $delDia = $franjas->filter(fn (Disponibilidad $franja): bool => $franja->dia_semana === $sesion->diaSemana)->sortBy('hora_inicio');

        foreach ($delDia as $franja) {
            if ($franja->hora_inicio <= $cubiertaHasta && $franja->hora_fin > $cubiertaHasta) {
                $cubiertaHasta = $franja->hora_fin;
            }
        }

        return $cubiertaHasta >= $sesion->horaFin;
    }
}
