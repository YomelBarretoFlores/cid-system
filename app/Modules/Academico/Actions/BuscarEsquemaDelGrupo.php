<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Modules\Academico\Enums\RegimenDeEsquema;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use Carbon\CarbonImmutable;

final readonly class BuscarEsquemaDelGrupo implements EsquemaDelGrupo
{
    public function __invoke(int $grupoId): ?EsquemaDeEvaluacion
    {
        $grupo = Grupo::query()->with(['ciclo', 'periodo'])->findOrFail($grupoId);
        $inicio = CarbonImmutable::parse($grupo->periodo->fecha_inicio)->toDateString();

        $esquema = EsquemaEvaluacion::query()
            ->with('componentes')
            ->aplicables($inicio, $grupo->ciclo->idioma_id, $grupo->ciclo->nivel, $grupo->programa_id, RegimenDeEsquema::delGrupo($grupo->es_acelerado))
            ->first();

        return $esquema === null ? null : new EsquemaDeEvaluacion(
            esquemaId: $esquema->id,
            nombre: $esquema->nombre,
            notaMaxima: (string) $esquema->nota_maxima,
            notaMinimaAprobatoria: (string) $esquema->nota_minima_aprobatoria,
            componentes: array_values($esquema->componentes->map(fn (ComponenteEvaluacion $componente): ComponenteDeEvaluacion => $componente->aDatos())->all()),
        );
    }
}
