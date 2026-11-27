<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\NotaAprobatoriaDelNivel;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Idioma;
use Carbon\CarbonImmutable;

// Same choice as BuscarEsquemaDelGrupo, for an exam that has no group.
final readonly class BuscarNotaAprobatoriaDelNivel implements NotaAprobatoriaDelNivel
{
    public function __invoke(string $idioma, NivelCurricular $nivel, CarbonImmutable $fecha): ?string
    {
        $idiomaId = Idioma::query()->where('nombre', $idioma)->value('id');

        $nota = EsquemaEvaluacion::query()->aplicables($fecha->toDateString(), $idiomaId, $nivel)->value('nota_minima_aprobatoria');

        return $nota === null ? null : (string) $nota;
    }
}
