<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Data\GrupoOfertado;
use App\Modules\Academico\Data\OfertaDelPeriodo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class ListarOfertaPublica implements OfertaPublica
{
    public function __invoke(): ?OfertaDelPeriodo
    {
        $hoy = CarbonImmutable::today('America/Lima')->toDateString();
        $abierto = $this->conMatriculaAbierta($hoy);
        $periodo = $abierto ?? $this->conMatriculaPorAbrir($hoy);

        if (! $periodo instanceof Periodo) {
            return null;
        }

        return new OfertaDelPeriodo(
            periodo: $periodo->nombre,
            matriculaAbierta: $abierto instanceof Periodo,
            matriculaInicio: $periodo->matricula_inicio,
            matriculaFin: $periodo->matricula_extemporanea_fin ?? $periodo->matricula_fin,
            clasesInicio: $periodo->fecha_inicio,
            clasesFin: $periodo->fecha_fin,
            grupos: $this->grupos($periodo),
        );
    }

    private function conMatriculaAbierta(string $hoy): ?Periodo
    {
        return Periodo::query()
            ->where('estado', EstadoPeriodo::MatriculaAbierta->value)
            ->conMatriculaEn($hoy)
            ->orderBy('fecha_inicio')
            ->first();
    }

    private function conMatriculaPorAbrir(string $hoy): ?Periodo
    {
        return Periodo::query()
            ->whereIn('estado', [EstadoPeriodo::Planificado->value, EstadoPeriodo::MatriculaAbierta->value])
            ->where('matricula_inicio', '>', $hoy)
            ->orderBy('matricula_inicio')
            ->first();
    }

    /**
     * @return list<GrupoOfertado>
     */
    private function grupos(Periodo $periodo): array
    {
        $grupos = Grupo::query()
            ->where('periodo_id', $periodo->id)
            ->whereIn('estado', [EstadoGrupo::Programado->value, EstadoGrupo::Abierto->value])
            ->whereHas('ciclo.idioma', fn (Builder $idioma) => $idioma->where('activo', true))
            ->with(['ciclo.idioma', 'modalidadDeDictado', 'horarios'])
            ->get()
            ->sortBy([
                fn (Grupo $a, Grupo $b): int => $a->ciclo->idioma->nombre <=> $b->ciclo->idioma->nombre,
                fn (Grupo $a, Grupo $b): int => $a->ciclo->numero <=> $b->ciclo->numero,
                fn (Grupo $a, Grupo $b): int => $a->codigo <=> $b->codigo,
            ]);

        return array_values($grupos->map(GrupoOfertado::desde(...))->all());
    }
}
