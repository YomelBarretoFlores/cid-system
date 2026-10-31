<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Docencia\Contracts\SilaboDelGrupo;
use App\Modules\Docencia\Data\DatosDelSilabo;
use App\Modules\Docencia\Data\EvaluacionDelSilabo;
use App\Modules\Docencia\Data\SemanaDelSilabo;
use App\Modules\Docencia\Models\EvaluacionDeSilabo;
use App\Modules\Docencia\Models\SemanaDeSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Modules\Docencia\Support\SemanasDelPeriodo;
use Carbon\CarbonImmutable;

// Weeks come from the period dates and evaluation rows from the group's current scheme, as the teacher saw them when writing.
final readonly class LeerSilaboDelGrupo implements SilaboDelGrupo
{
    public function __construct(
        private HorariosDeGrupos $horariosDeGrupos,
        private EsquemaDelGrupo $esquemaDelGrupo,
    ) {}

    public function __invoke(int $grupoId): ?DatosDelSilabo
    {
        $silabo = Silabo::query()->with(['semanas', 'evaluaciones'])->where('grupo_id', $grupoId)->first();
        $grupo = ($this->horariosDeGrupos)([$grupoId])[$grupoId] ?? null;

        if ($silabo === null || $grupo === null) {
            return null;
        }

        return new DatosDelSilabo(
            grupoId: $grupoId,
            estado: $silabo->estado,
            sumilla: $silabo->sumilla,
            competencias: $silabo->competencias,
            capacidades: $silabo->capacidades,
            metodologia: $silabo->metodologia,
            bibliografia: $silabo->bibliografia,
            publicadoEn: $silabo->publicado_en?->toImmutable(),
            semanas: $this->semanas($silabo, SemanasDelPeriodo::rangos($grupo->periodoInicio, $grupo->periodoFin)),
            evaluaciones: $this->evaluaciones($silabo, $grupoId),
        );
    }

    /**
     * @param  array<int, array{CarbonImmutable, CarbonImmutable}>  $rangos
     * @return list<SemanaDelSilabo>
     */
    private function semanas(Silabo $silabo, array $rangos): array
    {
        $guardadas = $silabo->semanas->keyBy('semana');

        return array_map(function (int $numero, array $rango) use ($guardadas): SemanaDelSilabo {
            /** @var SemanaDeSilabo|null $fila */
            $fila = $guardadas->get($numero);

            return new SemanaDelSilabo($numero, $rango[0], $rango[1], $fila?->unidad, $fila?->saber, $fila?->saber_hacer, $fila?->saber_ser, $fila?->actividades, $fila?->recursos);
        }, array_keys($rangos), $rangos);
    }

    /**
     * @return list<EvaluacionDelSilabo>
     */
    private function evaluaciones(Silabo $silabo, int $grupoId): array
    {
        $guardadas = $silabo->evaluaciones->keyBy('componente_codigo');

        return array_map(function (ComponenteDeEvaluacion $componente) use ($guardadas): EvaluacionDelSilabo {
            /** @var EvaluacionDeSilabo|null $fila */
            $fila = $guardadas->get($componente->codigo);

            return new EvaluacionDelSilabo($componente->codigo, $componente->nombre, $componente->peso, $fila?->indicadores, $fila?->instrumento, $fila?->semana);
        }, ($this->esquemaDelGrupo)($grupoId)->componentes ?? []);
    }
}
