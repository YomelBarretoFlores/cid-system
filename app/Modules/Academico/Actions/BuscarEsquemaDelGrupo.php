<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final readonly class BuscarEsquemaDelGrupo implements EsquemaDelGrupo
{
    public function __invoke(int $grupoId): ?EsquemaDeEvaluacion
    {
        $grupo = Grupo::query()->with(['ciclo', 'periodo'])->findOrFail($grupoId);
        $inicio = CarbonImmutable::parse($grupo->periodo->fecha_inicio)->toDateString();

        $esquema = EsquemaEvaluacion::query()
            ->with('componentes')
            ->where('vigente_desde', '<=', $inicio)
            ->where(fn (Builder $query) => $query->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $inicio))
            ->where(fn (Builder $query) => $query->whereNull('idioma_id')->orWhere('idioma_id', $grupo->ciclo->idioma_id))
            ->where(fn (Builder $query) => $query->whereNull('nivel')->orWhere('nivel', $grupo->ciclo->nivel))
            ->orderByRaw('idioma_id IS NULL, nivel IS NULL')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->first();

        return $esquema === null ? null : new EsquemaDeEvaluacion(
            esquemaId: $esquema->id,
            nombre: $esquema->nombre,
            notaMaxima: (string) $esquema->nota_maxima,
            notaMinimaAprobatoria: (string) $esquema->nota_minima_aprobatoria,
            componentes: array_values($esquema->componentes->map(fn (ComponenteEvaluacion $componente): ComponenteDeEvaluacion => new ComponenteDeEvaluacion(
                $componente->id,
                $componente->codigo,
                $componente->nombre,
                $componente->grupo,
            ))->all()),
        );
    }
}
