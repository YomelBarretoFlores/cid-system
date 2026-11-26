<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\NotaAprobatoriaDelNivel;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Idioma;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

// Same choice as BuscarEsquemaDelGrupo, for an exam that has no group.
final readonly class BuscarNotaAprobatoriaDelNivel implements NotaAprobatoriaDelNivel
{
    public function __invoke(string $idioma, NivelCurricular $nivel, CarbonImmutable $fecha): ?string
    {
        $idiomaId = Idioma::query()->where('nombre', $idioma)->value('id');
        $dia = $fecha->toDateString();

        $nota = EsquemaEvaluacion::query()
            ->where('vigente_desde', '<=', $dia)
            ->where(fn (Builder $query) => $query->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $dia))
            ->where(fn (Builder $query) => $query->whereNull('idioma_id')->orWhere('idioma_id', $idiomaId))
            ->where(fn (Builder $query) => $query->whereNull('nivel')->orWhere('nivel', $nivel))
            ->orderByRaw('idioma_id IS NULL, nivel IS NULL')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->value('nota_minima_aprobatoria');

        return $nota === null ? null : (string) $nota;
    }
}
