<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Certificacion\Contracts\AvanceDeNotas;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Data\AvanceDeNotasDelEstudiante;
use App\Modules\Certificacion\Data\FilaDelActa;
use App\Modules\Certificacion\Data\NotaDelComponente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class ConsultarAvanceDeNotas implements AvanceDeNotas
{
    public function __construct(private PrepararActaDelGrupo $prepararActa) {}

    public function __invoke(int $grupoId, int $matriculaId): AvanceDeNotasDelEstudiante
    {
        try {
            $acta = ($this->prepararActa)($grupoId);
        } catch (ReglaDeNegocioIncumplida) {
            return AvanceDeNotasDelEstudiante::noDisponible();
        }

        $fila = array_find($acta->filas, fn (FilaDelActa $fila): bool => $fila->matriculaId === $matriculaId);

        return new AvanceDeNotasDelEstudiante(
            disponible: true,
            actaCerrada: $acta->estaCerrada(),
            componentes: $this->componentes($acta, $fila),
            promedio: $fila?->promedio,
            estadoFinal: $acta->estaCerrada() ? $fila?->estadoFinal : null,
            notaMinimaAprobatoria: $acta->notaMinimaAprobatoria,
            promedioHabilidades: $fila?->promedioHabilidades,
            promedioExamenes: $fila?->promedioExamenes,
        );
    }

    /**
     * @return list<NotaDelComponente>
     */
    private function componentes(ActaDelGrupo $acta, ?FilaDelActa $fila): array
    {
        return array_map(
            fn (ComponenteDeEvaluacion $componente): NotaDelComponente => new NotaDelComponente($componente->codigo, $componente->nombre, $componente->grupo, $fila->notas[$componente->id] ?? null),
            $acta->componentes,
        );
    }
}
